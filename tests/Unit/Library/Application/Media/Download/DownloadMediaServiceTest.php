<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Download;

use App\Library\Application\Media\Download\DownloadMediaRequest;
use App\Library\Application\Media\Download\DownloadMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadMediaServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = new InMemoryMediaRepository();
        $storage = MediaStorageMother::inMemory();

        $media = MediaMother::uploadedMedia();

        $storage->store($media->id(), null, 'png', MediaStorageMother::localFile('the-contents'));

        $repository->add($media);

        $sut = $this->createSUT($repository, $storage);

        $result = $sut(new DownloadMediaRequest($media->id()), UserMother::adminUser());

        $this->assertEquals('logo.png', $result->fileName);
        $this->assertEquals('image/png', $result->mimeType);
        $this->assertEquals('the-contents', stream_get_contents($result->stream));
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository(), MediaStorageMother::inMemory());

        $sut(new DownloadMediaRequest(MediaId::generate()), UserMother::adminUser());
    }

    public function testMediaWithoutStoredContents(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $repository = new InMemoryMediaRepository();

        $media = MediaMother::uploadedMedia();

        $repository->add($media);

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $sut(new DownloadMediaRequest($media->id()), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryMediaRepository();
        $storage = MediaStorageMother::inMemory();

        $media = MediaMother::fromNameAndAccount('logo.png', AccountId::generate());

        $storage->store($media->id(), null, 'png', MediaStorageMother::localFile());

        $repository->add($media);

        $sut = $this->createSUT($repository, $storage);

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::User, AccountId::generate());

        $sut(new DownloadMediaRequest($media->id()), $foreignUser);
    }

    private function createSUT(InMemoryMediaRepository $repository, InMemoryMediaStorage $storage): DownloadMediaService
    {
        return new DownloadMediaService($repository, $storage, MediaAuthorizerMother::create());
    }
}
