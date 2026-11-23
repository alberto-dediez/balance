<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\Remove;

use App\Library\Application\Folder\Remove\RemoveFolderRequest;
use App\Library\Application\Folder\Remove\RemoveFolderService;
use App\Library\Application\Media\Remove\RemoveMediaService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderNotRemovedException;
use App\Library\Domain\Model\Folder\FolderRemoved;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Domain\Model\Media\MediaRemoved;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveFolderServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryFolderRepository $repository;

    private InMemoryMediaRepository $mediaRepository;

    private InMemoryMediaStorage $storage;

    private RemoveFolderService $sut;

    private AccountId $accountId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = FolderRepositoryMother::inMemory();
        $this->mediaRepository = MediaRepositoryMother::inMemory();
        $this->storage = MediaStorageMother::inMemory();
        $this->sut = $this->createSUT($this->storage);
        $this->accountId = AccountId::generate();
    }

    public function testAnEmptyFolderIsRemoved(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

        $this->assertSame(0, $result->removedMedia);
        $this->assertSame(0, $result->removedSubfolders);
        $this->assertNull($this->repository->find($folder->id()));
        $this->assertInstanceOf(FolderRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testTheFilesOfTheFolderAreRemoved(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $otherFolder = FolderMother::fromNameAndAccount('Facturas', $this->accountId);

        $this->repository->add($folder);
        $this->repository->add($otherFolder);

        $own = $this->storedMedia('own.png', $folder);
        $byAnotherUser = $this->storedMedia('other.png', $folder, UserId::generate());
        $elsewhere = $this->storedMedia('elsewhere.png', $otherFolder);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

        $this->assertSame(2, $result->removedMedia);
        $this->assertNull($this->repository->find($folder->id()));

        $this->assertNull($this->mediaRepository->find($own->id()));
        $this->assertNull($this->mediaRepository->find($byAnotherUser->id()));
        $this->assertFalse($this->storage->exists($own->path()));
        $this->assertFalse($this->storage->exists($byAnotherUser->path()));

        $this->assertNotNull($this->mediaRepository->find($elsewhere->id()));
        $this->assertTrue($this->storage->exists($elsewhere->path()));
        $this->assertNotNull($this->repository->find($otherFolder->id()));

        $this->assertCount(2, $this->eventsOf(MediaRemoved::class));
        $this->assertCount(0, $this->eventsOf(MediaMoved::class));
    }

    public function testRemovingASubfolderRemovesOnlyItsFiles(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $subfolder = FolderMother::subfolderOf($folder, '2024');
        $sibling = FolderMother::subfolderOf($folder, '2025');

        $this->repository->add($folder);
        $this->repository->add($subfolder);
        $this->repository->add($sibling);

        $inSubfolder = $this->storedMedia('2024.png', $subfolder);
        $inFolder = $this->storedMedia('contract.png', $folder);
        $inSibling = $this->storedMedia('2025.png', $sibling);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($subfolder->id()), $this->owner());

        $this->assertSame(1, $result->removedMedia);
        $this->assertSame(0, $result->removedSubfolders);
        $this->assertNull($this->repository->find($subfolder->id()));
        $this->assertNull($this->mediaRepository->find($inSubfolder->id()));

        $this->assertNotNull($this->repository->find($folder->id()));
        $this->assertNotNull($this->repository->find($sibling->id()));
        $this->assertNotNull($this->mediaRepository->find($inFolder->id()));
        $this->assertNotNull($this->mediaRepository->find($inSibling->id()));
    }

    public function testRemovingAFolderRemovesItsSubfoldersAndAllTheirFiles(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $first = FolderMother::subfolderOf($folder, '2024');
        $second = FolderMother::subfolderOf($folder, '2025');

        $this->repository->add($folder);
        $this->repository->add($first);
        $this->repository->add($second);

        $media = [
            $this->storedMedia('contract.png', $folder),
            $this->storedMedia('2024-a.png', $first),
            $this->storedMedia('2024-b.png', $first),
            $this->storedMedia('2025.png', $second),
        ];

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

        $this->assertSame(4, $result->removedMedia);
        $this->assertSame(2, $result->removedSubfolders);

        $this->assertNull($this->repository->find($folder->id()));
        $this->assertNull($this->repository->find($first->id()));
        $this->assertNull($this->repository->find($second->id()));

        foreach ($media as $item) {
            $this->assertNull($this->mediaRepository->find($item->id()));
        }

        $this->assertCount(3, $this->eventsOf(FolderRemoved::class));
        $this->assertCount(4, $this->eventsOf(MediaRemoved::class));
    }

    public function testTheFolderIsKeptWhenTheStorageFails(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $first = $this->storedMedia('first.png', $folder);
        $second = $this->storedMedia('second.png', $folder);

        $this->storage->markUnavailable();

        $this->spySubscriber = new SpyDomainEventSubscriber();

        try {
            ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

            $this->fail('FolderNotRemovedException was expected');
        } catch (FolderNotRemovedException $exception) {
            $this->assertSame(2, $exception->failedMedia());
        }

        $this->assertNotNull($this->repository->find($folder->id()));
        $this->assertNotNull($this->mediaRepository->find($first->id()));
        $this->assertNotNull($this->mediaRepository->find($second->id()));
        $this->assertCount(0, $this->eventsOf(FolderRemoved::class));
        $this->assertCount(0, $this->eventsOf(MediaRemoved::class));
    }

    public function testASubfolderWithAFailedFileIsKeptAndSoIsTheFolder(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $failing = FolderMother::subfolderOf($folder, '2024');
        $removable = FolderMother::subfolderOf($folder, '2025');

        $this->repository->add($folder);
        $this->repository->add($failing);
        $this->repository->add($removable);

        $failingMedia = MediaMother::inFolder('failing.png', $failing->id(), $this->accountId);

        $storage = new class ($failingMedia->path()) extends InMemoryMediaStorage {
            public function __construct(private readonly string $failingPath) {}

            public function delete(string $path): void
            {
                if ($path === $this->failingPath) {
                    throw new NotStoredMediaException(sprintf('Unable to delete "%s"', $path));
                }

                parent::delete($path);
            }
        };

        $this->storage = $storage;
        $this->sut = $this->createSUT($storage);

        $this->mediaRepository->add($failingMedia);
        $storage->write($failingMedia->path(), MediaStorageMother::localFile());

        $kept = $this->storedMedia('kept.png', $failing);
        $inRemovable = $this->storedMedia('2025.png', $removable);
        $inFolder = $this->storedMedia('contract.png', $folder);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        try {
            ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

            $this->fail('FolderNotRemovedException was expected');
        } catch (FolderNotRemovedException $exception) {
            $this->assertSame(1, $exception->failedMedia());
        }

        $this->assertNotNull($this->repository->find($folder->id()));
        $this->assertNotNull($this->repository->find($failing->id()));
        $this->assertNull($this->repository->find($removable->id()));

        $this->assertNotNull($this->mediaRepository->find($failingMedia->id()));
        $this->assertNull($this->mediaRepository->find($kept->id()));
        $this->assertNull($this->mediaRepository->find($inRemovable->id()));
        $this->assertNull($this->mediaRepository->find($inFolder->id()));

        $this->assertCount(1, $this->eventsOf(FolderRemoved::class));
    }

    public function testAUserCannotRemove(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $media = $this->storedMedia('own.png', $folder);

        try {
            ($this->sut)(
                new RemoveFolderRequest($folder->id()),
                UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId),
            );

            $this->fail('A user should not remove a folder');
        } catch (UnauthorizedActionException) {
        }

        $this->assertNotNull($this->repository->find($folder->id()));
        $this->assertNotNull($this->mediaRepository->find($media->id())?->folderId());
    }

    public function testUnknownFolder(): void
    {
        $this->expectException(FolderNotFoundException::class);

        ($this->sut)(new RemoveFolderRequest(FolderId::generate()), $this->owner());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new RemoveFolderRequest(FolderId::generate())));
    }

    private function createSUT(InMemoryMediaStorage $storage): RemoveFolderService
    {
        return new RemoveFolderService(
            $this->repository,
            $this->mediaRepository,
            new RemoveMediaService($this->mediaRepository, $storage, MediaAuthorizerMother::create()),
            FolderAuthorizerMother::create(),
        );
    }

    private function storedMedia(string $name, Folder $folder, ?UserId $uploadedBy = null): Media
    {
        $media = MediaMother::inFolder($name, $folder->id(), $this->accountId, $uploadedBy);

        $this->mediaRepository->add($media);
        $this->storage->write($media->path(), MediaStorageMother::localFile());

        return $media;
    }

    /**
     * @param class-string $class
     *
     * @return list<object>
     */
    private function eventsOf(string $class): array
    {
        return array_values(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof $class));
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
