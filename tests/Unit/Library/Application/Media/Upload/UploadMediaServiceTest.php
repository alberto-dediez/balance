<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Upload;

use App\Library\Application\Media\Upload\UploadMediaRequest;
use App\Library\Application\Media\Upload\UploadMediaService;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaUploaded;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
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

    /**
     * The visibility is a decision of whoever uploads the file, whatever its role: the
     * plain users choose it in the library, and the forms embedding a media picker
     * impose it on the file they receive.
     */
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
        $this->assertTrue($media->isShared());
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

    private function createSUT(InMemoryMediaRepository $repository, InMemoryMediaStorage $storage): UploadMediaService
    {
        return new UploadMediaService($repository, $storage, MediaAuthorizerMother::create());
    }
}
