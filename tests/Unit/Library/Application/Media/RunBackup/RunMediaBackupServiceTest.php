<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\RunBackup;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Application\Media\RunBackup\RunMediaBackupRequest;
use App\Library\Application\Media\RunBackup\RunMediaBackupService;
use App\Library\Domain\Model\Backup\MediaBackupFailed;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaBackupRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Backup\MediaBackupMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\Persistence\SpyUnitOfWorkReleaser;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RunMediaBackupServiceTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private InMemoryMediaBackupRepository $backups;

    private InMemoryMediaStorage $source;

    private InMemoryMediaStorage $backup;

    private \DateTimeImmutable $now;

    private SpyUnitOfWorkReleaser $unitOfWorkReleaser;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryMediaRepository();
        $this->backups = new InMemoryMediaBackupRepository();
        $this->source = MediaStorageMother::inMemory();
        $this->backup = MediaStorageMother::inMemory();
        $this->now = new \DateTimeImmutable('2026-12-31 04:00:00');
        $this->unitOfWorkReleaser = new SpyUnitOfWorkReleaser();
    }

    public function testUseCase(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());

        $spySubscriber = new SpyDomainEventSubscriber();

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertTrue($response->enabled);
        $this->assertSame(2, $response->copied);
        $this->assertSame(0, $response->alreadyBackedUp);
        $this->assertSame(0, $response->missingInSource);
        $this->assertSame(0, $response->purged);
        $this->assertSame([], $response->failures);
        $this->assertSame('contents of logo.png', $this->contents($this->backup, $logo->path()));
        $this->assertSame('contents of contract.pdf', $this->contents($this->backup, $document->path()));

        $row = $this->backups->find($logo->path());

        $this->assertNotNull($row);
        $this->assertSame((string) $logo->id(), $row->mediaId());
        $this->assertSame($this->now, $row->backedUpAt());
        $this->assertNull($row->removedAt());
        $this->assertNotNull($this->backups->find($document->path()));
        $this->assertSame([], $spySubscriber->events());
    }

    public function testSecondRunDoesNotTouchTheBackupStorage(): void
    {
        $this->addMedia(MediaMother::uploadedMedia());
        $this->addMedia(MediaMother::document());

        $this->createSUT()($this->request(), UserMother::systemUser());

        $backup = new class extends InMemoryMediaStorage {
            public int $calls = 0;

            public function write(string $path, string $localPath): void
            {
                ++$this->calls;

                parent::write($path, $localPath);
            }

            public function exists(string $path): bool
            {
                ++$this->calls;

                return parent::exists($path);
            }
        };

        $response = $this->createSUT($backup)($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->copied);
        $this->assertSame(2, $response->alreadyBackedUp);
        $this->assertSame(0, $backup->calls);
    }

    public function testMissingInSource(): void
    {
        $logo = MediaMother::uploadedMedia();
        $this->repository->add($logo);

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->copied);
        $this->assertSame(1, $response->missingInSource);
        $this->assertSame([], $response->failures);
        $this->assertNull($this->backups->find($logo->path()));
    }

    public function testWalksEveryPage(): void
    {
        $first = $this->addMedia(MediaMother::random());
        $second = $this->addMedia(MediaMother::random());
        $third = $this->addMedia(MediaMother::random());

        $response = $this->createSUT()(new RunMediaBackupRequest($this->now, batchSize: 1), UserMother::systemUser());

        $this->assertSame(3, $response->copied);
        $this->assertTrue($this->backup->exists($first->path()));
        $this->assertTrue($this->backup->exists($second->path()));
        $this->assertTrue($this->backup->exists($third->path()));
    }

    public function testReleasesTheUnitOfWorkAfterEveryCopyBatch(): void
    {
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());

        $this->createSUT(retentionDays: 0)(new RunMediaBackupRequest($this->now, batchSize: 1), UserMother::systemUser());

        $this->assertSame(3, $this->unitOfWorkReleaser->calls);
    }

    public function testReleasesTheUnitOfWorkAfterEveryPurgeBatch(): void
    {
        $this->backupFile('shared/first.png', $this->now->modify('-31 days'));
        $this->backupFile('shared/second.png', $this->now->modify('-32 days'));

        $response = $this->createSUT()(new RunMediaBackupRequest($this->now, batchSize: 1), UserMother::systemUser());

        $this->assertSame(2, $response->purged);
        $this->assertSame(2, $this->unitOfWorkReleaser->calls);
    }

    public function testUncaughtFailureIsNotifiedAndRethrown(): void
    {
        $this->repository = new class extends InMemoryMediaRepository {
            public function findByFilter(MediaFilter $filter): array
            {
                throw new \RuntimeException('Database unavailable');
            }
        };
        $this->backupFile('shared/old.png', $this->now->modify('-31 days'));

        $spySubscriber = new SpyDomainEventSubscriber();

        try {
            $this->createSUT()($this->request(), UserMother::systemUser());

            $this->fail('The failure should be rethrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database unavailable', $exception->getMessage());
        }

        $this->assertCount(1, $spySubscriber->events());

        $event = $spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaBackupFailed::class, $event);
        $this->assertSame(1, $event->failed());
        $this->assertSame('Database unavailable', $event->error());
        $this->assertTrue($this->backup->exists('shared/old.png'));
    }

    public function testNonPositiveBatchSize(): void
    {
        $this->addMedia(MediaMother::uploadedMedia());
        $this->addMedia(MediaMother::document());

        $response = $this->createSUT()(new RunMediaBackupRequest($this->now, batchSize: 0), UserMother::systemUser());

        $this->assertSame(2, $response->copied);
    }

    public function testRemovedMediaIsNotCopied(): void
    {
        $media = MediaMother::uploadedMedia();
        $media->remove();
        $this->addMedia($media);

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->copied);
        $this->assertSame(0, $response->missingInSource);
        $this->assertFalse($this->backup->exists($media->path()));
    }

    public function testPurgesTheBackupOfAFileRemovedMoreThanTheRetentionAgo(): void
    {
        $this->backupFile('shared/old.png', $this->now->modify('-31 days'));

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame(1, $response->purged);
        $this->assertFalse($this->backup->exists('shared/old.png'));
        $this->assertNull($this->backups->find('shared/old.png'));
    }

    public function testKeepsTheBackupOfAFileRemovedWithinTheRetention(): void
    {
        $this->backupFile('shared/recent.png', $this->now->modify('-29 days'));

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->purged);
        $this->assertTrue($this->backup->exists('shared/recent.png'));
        $this->assertNotNull($this->backups->find('shared/recent.png'));
    }

    public function testZeroRetentionNeverPurges(): void
    {
        $this->backupFile('shared/old.png', $this->now->modify('-400 days'));

        $response = $this->createSUT(retentionDays: 0)($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->purged);
        $this->assertTrue($this->backup->exists('shared/old.png'));
        $this->assertNotNull($this->backups->find('shared/old.png'));
    }

    public function testFailuresAreCollectedAndNotified(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());
        $this->backup->markUnavailable();

        $spySubscriber = new SpyDomainEventSubscriber();

        $response = $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame(0, $response->copied);
        $this->assertCount(2, $response->failures);
        $this->assertContains(sprintf('%s: Media storage unavailable for "%s"', (string) $logo->id(), $logo->path()), $response->failures);
        $this->assertNull($this->backups->find($logo->path()));
        $this->assertNull($this->backups->find($document->path()));
        $this->assertCount(1, $spySubscriber->events());

        $event = $spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaBackupFailed::class, $event);
        $this->assertSame(2, $event->failed());
        $this->assertSame($response->failures[0], $event->error());
    }

    public function testAbortsAtTheMaximumOfFailuresAndDoesNotPurge(): void
    {
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());
        $this->backupFile('shared/old.png', $this->now->modify('-31 days'));

        $backup = new class extends InMemoryMediaStorage {
            public bool $failing = false;

            public function write(string $path, string $localPath): void
            {
                if ($this->failing) {
                    throw new \RuntimeException('Bucket unavailable');
                }

                parent::write($path, $localPath);
            }
        };
        $backup->write('shared/old.png', MediaStorageMother::localFile());
        $backup->failing = true;

        $response = $this->createSUT($backup)(new RunMediaBackupRequest($this->now, maxFailures: 2), UserMother::systemUser());

        $this->assertCount(2, $response->failures);
        $this->assertSame(0, $response->purged);
        $this->assertTrue($backup->exists('shared/old.png'));
        $this->assertNotNull($this->backups->find('shared/old.png'));
    }

    public function testCleanRunDoesNotPublishTheFailure(): void
    {
        $this->addMedia(MediaMother::uploadedMedia());

        $spySubscriber = new SpyDomainEventSubscriber();

        $this->createSUT()($this->request(), UserMother::systemUser());

        $this->assertSame([], $spySubscriber->events());
    }

    public function testDisabledDoesNothing(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $this->backupFile('shared/old.png', $this->now->modify('-31 days'));
        $this->backup->markUnavailable();

        $spySubscriber = new SpyDomainEventSubscriber();

        $response = $this->createSUT(enabled: false)($this->request(), UserMother::systemUser());

        $this->assertFalse($response->enabled);
        $this->assertSame(0, $response->copied);
        $this->assertSame(0, $response->alreadyBackedUp);
        $this->assertSame(0, $response->missingInSource);
        $this->assertSame(0, $response->purged);
        $this->assertSame([], $response->failures);
        $this->assertNull($this->backups->find($logo->path()));
        $this->assertNotNull($this->backups->find('shared/old.png'));
        $this->assertSame([], $spySubscriber->events());
    }

    public function testWithNowKeepsTheOtherValues(): void
    {
        $now = new \DateTimeImmutable('2027-01-01 04:00:00');

        $request = (new RunMediaBackupRequest($this->now, batchSize: 5, maxFailures: 3))->withNow($now);

        $this->assertSame($now, $request->now);
        $this->assertSame(5, $request->batchSize);
        $this->assertSame(3, $request->maxFailures);
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT()->isHandled($this->request()));
    }

    private function createSUT(?MediaStorage $backup = null, bool $enabled = true, int $retentionDays = 30): RunMediaBackupService
    {
        return new RunMediaBackupService(
            $this->repository,
            $this->backups,
            $this->source,
            $backup ?? $this->backup,
            new MediaStorageFileCopier(),
            new NullLogger(),
            $this->unitOfWorkReleaser,
            $enabled,
            $retentionDays,
        );
    }

    private function request(): RunMediaBackupRequest
    {
        return new RunMediaBackupRequest($this->now);
    }

    private function addMedia(Media $media): Media
    {
        $this->repository->add($media);
        $this->source->write($media->path(), MediaStorageMother::localFile(sprintf('contents of %s', $media->fileName())));

        return $media;
    }

    private function backupFile(string $path, \DateTimeImmutable $removedAt): void
    {
        $this->backup->write($path, MediaStorageMother::localFile());
        $this->backups->save(MediaBackupMother::removedAt($path, $removedAt));
    }

    private function contents(InMemoryMediaStorage $storage, string $path): string
    {
        $stream = $storage->read($path);

        $contents = (string) stream_get_contents($stream);

        fclose($stream);

        return $contents;
    }
}
