<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Update;

use App\Library\Application\Media\MediaFolderGuard;
use App\Library\Application\Media\Update\UpdateMediaRequest;
use App\Library\Application\Media\Update\UpdateMediaService;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Media\MediaDetailsChanged;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Domain\Model\Media\MediaVisibilityChanged;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMediaServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: 'Logotipo corporativo',
                description: 'El logotipo en color',
                alternativeText: 'Logotipo',
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals('Logotipo corporativo', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof MediaDetailsChanged);
        $this->assertEquals('Logotipo', $lastEvent->alternativeText());
    }

    public function testOnlyAnAdminSharesAFileWithTheOtherAccounts(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdateMediaRequest(mediaId: $media->id(), name: $media->name(), shared: true),
            UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
        );
    }

    public function testAnAdminSharesAFileWithTheOtherAccounts(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateMediaRequest(mediaId: $media->id(), name: $media->name(), shared: true),
            UserMother::adminUser(),
        );

        $this->assertTrue($result->shared);
        $this->assertEquals(MediaVisibility::System, $result->visibility);
    }

    public function testNothingIsRecordedWhenNothingChanges(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                description: $media->description(),
                alternativeText: $media->alternativeText(),
            ),
            UserMother::adminUser(),
        );

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertFalse($lastEvent instanceof MediaDetailsChanged);
    }

    public function testTheVisibilityIsChanged(): void
    {
        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                visibility: MediaVisibility::Public,
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(MediaVisibility::Public, $result->visibility);
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    public function testAnyUserEditingTheMediaChangesTheVisibility(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId);

        $result = $sut(
            new UpdateMediaRequest(
                mediaId: $media->id(),
                name: $media->name(),
                visibility: MediaVisibility::System,
            ),
            $owner,
        );

        $this->assertEquals(MediaVisibility::System, $result->visibility);
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaVisibilityChanged);
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository());

        $sut(new UpdateMediaRequest(mediaId: MediaId::generate(), name: 'Whatever'), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $repository->add($media);

        $sut = $this->createSUT($repository);

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Owner, AccountId::generate());

        $sut(new UpdateMediaRequest(mediaId: $media->id(), name: 'Whatever'), $foreignUser);
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryMediaRepository());

        $this->assertTrue($sut->isHandled(new UpdateMediaRequest(mediaId: MediaId::generate(), name: 'Whatever')));
    }

    public function testChangingTheFolderMovesTheMedia(): void
    {
        $repository = new InMemoryMediaRepository();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);
        $folders->add($folder);

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);
        $repository->add($media);

        $sut = $this->createSUT($repository, $folders);

        $result = $sut(
            new UpdateMediaRequest(mediaId: $media->id(), name: $media->name(), folderId: $folder->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
        );

        $this->assertNotNull($result->folderId);
        $this->assertTrue($result->folderId->equals($folder->id()));
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaMoved);
    }

    public function testKeepingTheFolderDoesNotMoveTheMedia(): void
    {
        $repository = new InMemoryMediaRepository();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);
        $folders->add($folder);

        $media = MediaMother::inFolder('logo.png', $folder->id(), $accountId);
        $repository->add($media);

        $sut = $this->createSUT($repository, $folders);

        $sut(
            new UpdateMediaRequest(mediaId: $media->id(), name: $media->name(), folderId: $folder->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
        );

        $moved = array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof MediaMoved);

        $this->assertCount(1, $moved);
    }

    public function testAFolderOfAnotherAccountIsRejected(): void
    {
        $repository = new InMemoryMediaRepository();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $foreign = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());
        $folders->add($foreign);

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);
        $repository->add($media);

        $sut = $this->createSUT($repository, $folders);

        $this->expectException(FolderNotFoundException::class);

        $sut(
            new UpdateMediaRequest(mediaId: $media->id(), name: $media->name(), folderId: $foreign->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
        );
    }

    public function testAnInvalidFolderLeavesTheMediaUntouched(): void
    {
        $repository = new InMemoryMediaRepository();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $foreign = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());
        $folders->add($foreign);

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);
        $repository->add($media);

        $sut = $this->createSUT($repository, $folders);

        $eventsBefore = count($this->spySubscriber->events());
        $exception = null;

        try {
            $sut(
                new UpdateMediaRequest(mediaId: $media->id(), name: 'Nuevo nombre', folderId: $foreign->id()),
                UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
            );
        } catch (FolderNotFoundException $folderNotFound) {
            $exception = $folderNotFound;
        }

        $this->assertInstanceOf(FolderNotFoundException::class, $exception);
        $this->assertSame('logo.png', $media->name());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testAnUnauthorizedSharingLeavesTheMediaUntouched(): void
    {
        $repository = new InMemoryMediaRepository();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);
        $repository->add($media);

        $sut = $this->createSUT($repository);

        $eventsBefore = count($this->spySubscriber->events());
        $exception = null;

        try {
            $sut(
                new UpdateMediaRequest(mediaId: $media->id(), name: 'Nuevo nombre', shared: true),
                UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId),
            );
        } catch (UnauthorizedActionException $unauthorized) {
            $exception = $unauthorized;
        }

        $this->assertInstanceOf(UnauthorizedActionException::class, $exception);
        $this->assertSame('logo.png', $media->name());
        $this->assertFalse($media->isShared());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    private function createSUT(InMemoryMediaRepository $repository, ?InMemoryFolderRepository $folders = null): UpdateMediaService
    {
        return new UpdateMediaService(
            $repository,
            MediaAuthorizerMother::create(),
            new MediaFolderGuard($folders ?? FolderRepositoryMother::inMemory(), FolderAuthorizerMother::create()),
        );
    }
}
