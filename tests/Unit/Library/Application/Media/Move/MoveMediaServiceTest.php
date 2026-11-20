<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Move;

use App\Library\Application\Media\MediaFolderGuard;
use App\Library\Application\Media\Move\MoveMediaRequest;
use App\Library\Application\Media\Move\MoveMediaService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
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
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MoveMediaServiceTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private InMemoryFolderRepository $folderRepository;

    private MoveMediaService $sut;

    private AccountId $accountId;

    private Folder $folder;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = MediaRepositoryMother::inMemory();
        $this->folderRepository = FolderRepositoryMother::inMemory();
        $this->sut = new MoveMediaService(
            $this->repository,
            MediaAuthorizerMother::create(),
            new MediaFolderGuard($this->folderRepository, FolderAuthorizerMother::create()),
        );
        $this->accountId = AccountId::generate();

        $this->folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->folderRepository->add($this->folder);
    }

    public function testItMovesTheMediaToAFolder(): void
    {
        $media = MediaMother::fromNameAndAccount('logo.png', $this->accountId);

        $this->repository->add($media);

        $spySubscriber = new SpyDomainEventSubscriber();

        $result = ($this->sut)(new MoveMediaRequest($media->id(), $this->folder->id()), $this->owner());

        $this->assertNotNull($result->folderId);
        $this->assertTrue($result->folderId->equals($this->folder->id()));
        $this->assertTrue($this->folder->id()->equals($this->repository->find($media->id())?->folderId() ?? MediaId::generate()));
        $this->assertInstanceOf(MediaMoved::class, $spySubscriber->lastEvent());
    }

    public function testItTakesTheMediaOutOfItsFolder(): void
    {
        $media = MediaMother::inFolder('logo.png', $this->folder->id(), $this->accountId);

        $this->repository->add($media);

        $result = ($this->sut)(new MoveMediaRequest($media->id()), $this->owner());

        $this->assertNull($result->folderId);
        $this->assertNull($this->repository->find($media->id())?->folderId());
    }

    public function testTheSameFolderRecordsNothing(): void
    {
        $media = MediaMother::inFolder('logo.png', $this->folder->id(), $this->accountId);

        $this->repository->add($media);

        $spySubscriber = new SpyDomainEventSubscriber();

        ($this->sut)(new MoveMediaRequest($media->id(), $this->folder->id()), $this->owner());

        $this->assertNull($spySubscriber->lastEvent());
    }

    public function testAUserCannotMoveAMediaUploadedBySomebodyElse(): void
    {
        $media = MediaMother::from(MediaId::generate(), 'logo.png', 'image/png', $this->accountId, UserId::generate());

        $this->repository->add($media);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new MoveMediaRequest($media->id(), $this->folder->id()),
            UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId),
        );
    }

    public function testAFolderOfAnotherAccountIsRejectedAndTheMediaIsLeftUntouched(): void
    {
        $media = MediaMother::fromNameAndAccount('logo.png', $this->accountId);

        $this->repository->add($media);

        $foreign = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());

        $this->folderRepository->add($foreign);

        try {
            ($this->sut)(new MoveMediaRequest($media->id(), $foreign->id()), $this->owner());

            $this->fail('A folder of another account should be rejected');
        } catch (FolderNotFoundException) {
        }

        $this->assertNull($this->repository->find($media->id())?->folderId());
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        ($this->sut)(new MoveMediaRequest(MediaId::generate(), $this->folder->id()), $this->owner());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new MoveMediaRequest(MediaId::generate())));
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
