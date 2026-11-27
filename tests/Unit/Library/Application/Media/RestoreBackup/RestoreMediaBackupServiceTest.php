<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\RestoreBackup;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Application\Media\RestoreBackup\RestoreMediaBackupRequest;
use App\Library\Application\Media\RestoreBackup\RestoreMediaBackupService;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\Persistence\SpyUnitOfWorkReleaser;
use PHPUnit\Framework\TestCase;

class RestoreMediaBackupServiceTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private InMemoryMediaStorage $primary;

    private InMemoryMediaStorage $backup;

    private SpyUnitOfWorkReleaser $unitOfWorkReleaser;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryMediaRepository();
        $this->primary = MediaStorageMother::inMemory();
        $this->backup = MediaStorageMother::inMemory();
        $this->unitOfWorkReleaser = new SpyUnitOfWorkReleaser();
    }

    public function testUseCase(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $this->backup->write($logo->path(), MediaStorageMother::localFile('contents of logo.png'));

        $response = $this->createSUT()(new RestoreMediaBackupRequest(), UserMother::systemUser());

        $this->assertSame(1, $response->restored);
        $this->assertSame(0, $response->alreadyInPrimary);
        $this->assertSame(0, $response->missingInBackup);
        $this->assertSame([], $response->failures);
        $this->assertSame('contents of logo.png', $this->contents($this->primary, $logo->path()));
    }

    public function testAlreadyInPrimary(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $this->primary->write($logo->path(), MediaStorageMother::localFile('current'));
        $this->backup->write($logo->path(), MediaStorageMother::localFile('backup'));

        $response = $this->createSUT()(new RestoreMediaBackupRequest(), UserMother::systemUser());

        $this->assertSame(0, $response->restored);
        $this->assertSame(1, $response->alreadyInPrimary);
        $this->assertSame('current', $this->contents($this->primary, $logo->path()));
    }

    public function testMissingInBackup(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());

        $response = $this->createSUT()(new RestoreMediaBackupRequest(), UserMother::systemUser());

        $this->assertSame(0, $response->restored);
        $this->assertSame(1, $response->missingInBackup);
        $this->assertFalse($this->primary->exists($logo->path()));
    }

    public function testDryRun(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $this->backup->write($logo->path(), MediaStorageMother::localFile());

        $response = $this->createSUT()(new RestoreMediaBackupRequest(dryRun: true), UserMother::systemUser());

        $this->assertSame(1, $response->restored);
        $this->assertFalse($this->primary->exists($logo->path()));
    }

    public function testRestoresASingleMedia(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());
        $this->backup->write($logo->path(), MediaStorageMother::localFile());
        $this->backup->write($document->path(), MediaStorageMother::localFile());

        $response = $this->createSUT()(new RestoreMediaBackupRequest(mediaId: $document->id()), UserMother::systemUser());

        $this->assertSame(1, $response->restored);
        $this->assertTrue($this->primary->exists($document->path()));
        $this->assertFalse($this->primary->exists($logo->path()));
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $this->createSUT()(new RestoreMediaBackupRequest(mediaId: MediaId::generate()), UserMother::systemUser());
    }

    public function testWalksEveryPage(): void
    {
        $first = $this->addMedia(MediaMother::random());
        $second = $this->addMedia(MediaMother::random());
        $this->backup->write($first->path(), MediaStorageMother::localFile());
        $this->backup->write($second->path(), MediaStorageMother::localFile());

        $response = $this->createSUT()(new RestoreMediaBackupRequest(batchSize: 1), UserMother::systemUser());

        $this->assertSame(2, $response->restored);
    }

    public function testFailuresAreCollected(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $this->backup->write($logo->path(), MediaStorageMother::localFile());
        $this->primary->markUnavailable();

        $response = $this->createSUT()(new RestoreMediaBackupRequest(), UserMother::systemUser());

        $this->assertSame(0, $response->restored);
        $this->assertSame([sprintf('%s: Media storage unavailable for "%s"', (string) $logo->id(), $logo->path())], $response->failures);
    }

    public function testReleasesTheUnitOfWorkAfterEveryBatch(): void
    {
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());
        $this->addMedia(MediaMother::random());

        $response = $this->createSUT()(new RestoreMediaBackupRequest(batchSize: 1), UserMother::systemUser());

        $this->assertSame(3, $response->missingInBackup);
        $this->assertSame(3, $this->unitOfWorkReleaser->calls);
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT()->isHandled(new RestoreMediaBackupRequest()));
    }

    private function createSUT(): RestoreMediaBackupService
    {
        return new RestoreMediaBackupService($this->repository, $this->primary, $this->backup, new MediaStorageFileCopier(), $this->unitOfWorkReleaser);
    }

    private function addMedia(Media $media): Media
    {
        $this->repository->add($media);

        return $media;
    }

    private function contents(InMemoryMediaStorage $storage, string $path): string
    {
        $stream = $storage->read($path);

        $contents = (string) stream_get_contents($stream);

        fclose($stream);

        return $contents;
    }
}
