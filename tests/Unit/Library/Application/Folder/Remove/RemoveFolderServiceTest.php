<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\Remove;

use App\Library\Application\Folder\Remove\RemoveFolderRequest;
use App\Library\Application\Folder\Remove\RemoveFolderService;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRemoved;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveFolderServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryFolderRepository $repository;

    private InMemoryMediaRepository $mediaRepository;

    private RemoveFolderService $sut;

    private AccountId $accountId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = FolderRepositoryMother::inMemory();
        $this->mediaRepository = MediaRepositoryMother::inMemory();
        $this->sut = new RemoveFolderService($this->repository, $this->mediaRepository, FolderAuthorizerMother::create());
        $this->accountId = AccountId::generate();
    }

    public function testAnEmptyFolderIsRemoved(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

        $this->assertSame(0, $result->releasedMedia);
        $this->assertNull($this->repository->find($folder->id()));
        $this->assertInstanceOf(FolderRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testTheFilesOfTheFolderAreReleasedAndKept(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $otherFolderId = FolderId::generate();

        $this->repository->add($folder);

        $own = MediaMother::inFolder('own.png', $folder->id(), $this->accountId);
        $byAnotherUser = MediaMother::inFolder('other.png', $folder->id(), $this->accountId, UserId::generate());
        $elsewhere = MediaMother::inFolder('elsewhere.png', $otherFolderId, $this->accountId);

        $this->mediaRepository->add($own);
        $this->mediaRepository->add($byAnotherUser);
        $this->mediaRepository->add($elsewhere);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new RemoveFolderRequest($folder->id()), $this->owner());

        $this->assertSame(2, $result->releasedMedia);

        $this->assertNull($this->mediaRepository->find($own->id())?->folderId());
        $this->assertNull($this->mediaRepository->find($byAnotherUser->id())?->folderId());
        $this->assertNotNull($this->mediaRepository->find($own->id()));
        $this->assertNotNull($this->mediaRepository->find($byAnotherUser->id()));
        $this->assertTrue($otherFolderId->equals($this->mediaRepository->find($elsewhere->id())?->folderId() ?? FolderId::generate()));

        $moved = array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof MediaMoved);

        $this->assertCount(2, $moved);
    }

    public function testAUserCannotRemove(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $media = MediaMother::inFolder('own.png', $folder->id(), $this->accountId);

        $this->mediaRepository->add($media);

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

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
