<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\MigrateStorage;

use App\Library\Application\Media\MigrateStorage\MigrateMediaStorageRequest;
use App\Library\Application\Media\MigrateStorage\MigrateMediaStorageService;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MigrateMediaStorageServiceTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryMediaRepository();
    }

    public function testUseCase(): void
    {
        $withAccount = $this->addMedia(MediaMother::fromNameAndAccount('logo.png', AccountId::generate()));
        $shared = $this->addMedia(MediaMother::document());

        $source = $this->sourceWith($withAccount, $shared);
        $target = MediaStorageMother::inMemory();

        $spySubscriber = new SpyDomainEventSubscriber();

        $response = $this->createSUT()(new MigrateMediaStorageRequest($source, $target), UserMother::systemUser());

        $this->assertSame(2, $response->migrated);
        $this->assertSame(0, $response->alreadyInTarget);
        $this->assertSame(0, $response->missingInSource);
        $this->assertSame(0, $response->deletedFromSource);
        $this->assertSame([], $response->failures);
        $this->assertSame('contents of logo.png', $this->contents($target, $withAccount));
        $this->assertSame('contents of contract.pdf', $this->contents($target, $shared));
        $this->assertTrue($source->exists($withAccount->path()));
        $this->assertTrue($source->exists($shared->path()));
        $this->assertSame([], $spySubscriber->events());
    }

    public function testSecondRunCopiesNothing(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());

        $source = $this->sourceWith($logo, $document);
        $target = MediaStorageMother::inMemory();

        $this->createSUT()(new MigrateMediaStorageRequest($source, $target), UserMother::systemUser());

        $response = $this->createSUT()(new MigrateMediaStorageRequest($source, $target), UserMother::systemUser());

        $this->assertSame(0, $response->migrated);
        $this->assertSame(2, $response->alreadyInTarget);
    }

    public function testMissingInSource(): void
    {
        $this->addMedia(MediaMother::uploadedMedia());

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest(MediaStorageMother::inMemory(), MediaStorageMother::inMemory()),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->missingInSource);
        $this->assertSame(0, $response->migrated);
        $this->assertSame([], $response->failures);
    }

    public function testRemovedMediaIsSkipped(): void
    {
        $media = MediaMother::uploadedMedia();
        $media->remove();
        $this->addMedia($media);

        $source = $this->sourceWith($media);
        $target = MediaStorageMother::inMemory();

        $response = $this->createSUT()(new MigrateMediaStorageRequest($source, $target), UserMother::systemUser());

        $this->assertSame(0, $response->migrated);
        $this->assertSame(0, $response->missingInSource);
        $this->assertFalse($target->exists($media->path()));
    }

    public function testDeleteSource(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());

        $source = $this->sourceWith($logo, $document);
        $target = MediaStorageMother::inMemoryWith([$document->path() => 'contents of contract.pdf']);

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest($source, $target, deleteSource: true),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->migrated);
        $this->assertSame(1, $response->alreadyInTarget);
        $this->assertSame(2, $response->deletedFromSource);
        $this->assertFalse($source->exists($logo->path()));
        $this->assertFalse($source->exists($document->path()));
        $this->assertTrue($target->exists($logo->path()));
    }

    public function testDryRun(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());

        $source = $this->sourceWith($logo);
        $target = MediaStorageMother::inMemory();

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest($source, $target, deleteSource: true, dryRun: true),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->migrated);
        $this->assertSame(0, $response->deletedFromSource);
        $this->assertFalse($target->exists($logo->path()));
        $this->assertTrue($source->exists($logo->path()));
    }

    public function testWalksEveryPage(): void
    {
        $first = $this->addMedia(MediaMother::random());
        $second = $this->addMedia(MediaMother::random());
        $third = $this->addMedia(MediaMother::random());

        $source = $this->sourceWith($first, $second, $third);
        $target = MediaStorageMother::inMemory();

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest($source, $target, batchSize: 1),
            UserMother::systemUser(),
        );

        $this->assertSame(3, $response->migrated);
        $this->assertTrue($target->exists($first->path()));
        $this->assertTrue($target->exists($second->path()));
        $this->assertTrue($target->exists($third->path()));
    }

    public function testNonPositiveBatchSize(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());

        $source = $this->sourceWith($logo, $document);
        $target = MediaStorageMother::inMemory();

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest($source, $target, batchSize: 0),
            UserMother::systemUser(),
        );

        $this->assertSame(2, $response->migrated);
        $this->assertTrue($target->exists($logo->path()));
        $this->assertTrue($target->exists($document->path()));
    }

    public function testFailuresAreCollected(): void
    {
        $logo = $this->addMedia(MediaMother::uploadedMedia());
        $document = $this->addMedia(MediaMother::document());

        $source = $this->sourceWith($logo, $document);
        $target = new class ($logo->path()) extends InMemoryMediaStorage {
            public function __construct(private readonly string $failingPath) {}

            public function write(string $path, string $localPath): void
            {
                if ($path === $this->failingPath) {
                    throw new NotStoredMediaException('Bucket unavailable');
                }

                parent::write($path, $localPath);
            }
        };

        $response = $this->createSUT()(
            new MigrateMediaStorageRequest($source, $target, deleteSource: true),
            UserMother::systemUser(),
        );

        $this->assertSame([sprintf('%s: Bucket unavailable', (string) $logo->id())], $response->failures);
        $this->assertSame(1, $response->migrated);
        $this->assertSame(1, $response->deletedFromSource);
        $this->assertTrue($source->exists($logo->path()));
        $this->assertFalse($target->exists($logo->path()));
        $this->assertTrue($target->exists($document->path()));
        $this->assertFalse($source->exists($document->path()));
    }

    public function testIsHandle(): void
    {
        $request = new MigrateMediaStorageRequest(MediaStorageMother::inMemory(), MediaStorageMother::inMemory());

        $this->assertTrue($this->createSUT()->isHandled($request));
    }

    private function createSUT(): MigrateMediaStorageService
    {
        return new MigrateMediaStorageService($this->repository);
    }

    private function addMedia(Media $media): Media
    {
        $this->repository->add($media);

        return $media;
    }

    private function sourceWith(Media ...$media): InMemoryMediaStorage
    {
        $files = [];

        foreach ($media as $item) {
            $files[$item->path()] = sprintf('contents of %s', $item->fileName());
        }

        return MediaStorageMother::inMemoryWith($files);
    }

    private function contents(InMemoryMediaStorage $storage, Media $media): string
    {
        $stream = $storage->read($media->path());

        $contents = (string) stream_get_contents($stream);

        fclose($stream);

        return $contents;
    }
}
