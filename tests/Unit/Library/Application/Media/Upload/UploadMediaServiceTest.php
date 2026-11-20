<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Upload;

use App\Library\Application\Media\MediaFolderGuard;
use App\Library\Application\Media\Upload\UploadMediaRequest;
use App\Library\Application\Media\Upload\UploadMediaService;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaMoved;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaUploaded;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UploadMediaServiceTest extends TestCase
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
        $storage = MediaStorageMother::inMemory();

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository, $storage);

        $result = $sut($this->createRequest(), $user);

        $this->assertNotEmpty((string) $result->mediaId);
        $this->assertEquals(MediaType::Image, $result->type);

        $mediaPersisted = $repository->find($result->mediaId);

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals('logo.png', $mediaPersisted->name());
        $this->assertTrue($storage->exists($mediaPersisted->path()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MediaUploaded);
        $this->assertEquals('logo.png', $lastEvent->fileName());
    }

    public function testTheFileNameIsUsedWhenNoNameIsGiven(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $request = $this->createRequest();
        $request->name = '';

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals('logo.png', $result->name);
    }

    public function testTheUploaderIsTheLoggedUserByDefault(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $user = UserMother::adminUser();

        $result = $sut($this->createRequest(), $user);

        $media = $repository->find($result->mediaId);

        $this->assertNotNull($media);
        $this->assertNotNull($media->uploadedBy());
        $this->assertTrue($media->uploadedBy()->equals($user->id()));
    }

    public function testSharedMediaIsUploaded(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $request = $this->createRequest();
        $request->visibility = MediaVisibility::Public;

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals(MediaVisibility::Public, $result->visibility);

        $media = $repository->find($result->mediaId);

        $this->assertNotNull($media);
        $this->assertTrue($media->isPublic());
    }

    public function testAnyUserUploadsSharedMedia(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $request = $this->createRequest();
        $request->visibility = MediaVisibility::System;

        $result = $sut($request, UserMother::fromUsernameAndRole('user@dediez.software', Role::User));

        $media = $repository->find($result->mediaId);

        $this->assertNotNull($media);
        $this->assertEquals(MediaVisibility::System, $media->visibility());

        $this->assertFalse($media->isShared());
    }

    public function testOnlyAnAdminUploadsAFileSharedWithTheOtherAccounts(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $request = $this->createRequest();
        $request->shared = true;

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, UserMother::fromUsernameAndRole('user@dediez.software', Role::User));
    }

    public function testAnAdminUploadsAFileSharedWithTheOtherAccounts(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $request = $this->createRequest();
        $request->shared = true;

        $result = $sut($request, UserMother::adminUser());

        $media = $repository->find($result->mediaId);

        $this->assertNotNull($media);
        $this->assertTrue($media->isShared());
        $this->assertEquals(MediaVisibility::System, $media->visibility());
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryMediaRepository(), MediaStorageMother::inMemory());

        $this->assertTrue($sut->isHandled($this->createRequest()));
    }

    private function createRequest(): UploadMediaRequest
    {
        return new UploadMediaRequest(
            localPath: MediaStorageMother::localFile(),
            fileName: 'logo.png',
            extension: 'png',
            mimeType: 'image/png',
            size: 2048,
            name: 'logo.png',
        );
    }

    public function testTheFileIsUploadedIntoTheChosenFolder(): void
    {
        $repository = new InMemoryMediaRepository();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);
        $folders->add($folder);

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory(), $folders);

        $request = $this->createRequest();
        $request->accountId = $accountId;
        $request->folderId = $folder->id();

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId));

        $media = $repository->find($result->mediaId);

        $this->assertNotNull($media);
        $this->assertNotNull($media->folderId());
        $this->assertTrue($media->folderId()->equals($folder->id()));

        $events = $this->spySubscriber->events();

        $this->assertTrue($events[count($events) - 2] instanceof MediaUploaded);
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaMoved);
    }

    public function testAnInvalidFolderStoresNothing(): void
    {
        $repository = new InMemoryMediaRepository();
        $storage = MediaStorageMother::inMemory();
        $folders = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $foreign = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());
        $folders->add($foreign);

        $sut = $this->createSUT($repository, $storage, $folders);

        $storage->markUnavailable();

        $request = $this->createRequest();
        $request->accountId = $accountId;
        $request->folderId = $foreign->id();

        try {
            $sut($request, UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId));

            $this->fail('A folder of another account should be rejected');
        } catch (FolderNotFoundException) {
        }

        $this->assertSame(0, $repository->totalResultsByFilter(new MediaFilter(limit: 0)));
        $this->assertNotInstanceOf(MediaUploaded::class, $this->spySubscriber->lastEvent());
    }

    public function testWithoutFolderTheFileStaysOutOfAnyFolder(): void
    {
        $repository = new InMemoryMediaRepository();

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $result = $sut($this->createRequest(), UserMother::adminUser());

        $this->assertNull($repository->find($result->mediaId)?->folderId());
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof MediaUploaded);
    }

    private function createSUT(InMemoryMediaRepository $repository, InMemoryMediaStorage $storage, ?InMemoryFolderRepository $folders = null): UploadMediaService
    {
        return new UploadMediaService(
            $repository,
            $storage,
            MediaAuthorizerMother::create(),
            new MediaFolderGuard($folders ?? FolderRepositoryMother::inMemory(), FolderAuthorizerMother::create()),
        );
    }
}
