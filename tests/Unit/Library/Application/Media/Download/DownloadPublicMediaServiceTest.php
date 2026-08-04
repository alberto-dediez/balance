<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Download;

use App\Library\Application\Media\Download\DownloadMediaRequest;
use App\Library\Application\Media\Download\DownloadPublicMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class DownloadPublicMediaServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = new InMemoryMediaRepository();
        $storage = MediaStorageMother::inMemory();

        $media = MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public, AccountId::generate());

        $storage->store($media->id(), null, 'pdf', MediaStorageMother::localFile('the-contents'));

        $repository->add($media);

        $sut = $this->createSUT($repository, $storage);

        $result = $sut(new DownloadMediaRequest($media->id()));

        $this->assertEquals('terms.pdf', $result->fileName);
        $this->assertEquals('the-contents', stream_get_contents($result->stream));
    }

    public function testMediaNotPublicIsNotServed(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $repository = new InMemoryMediaRepository();
        $storage = MediaStorageMother::inMemory();

        $media = MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, AccountId::generate());

        $storage->store($media->id(), null, 'pdf', MediaStorageMother::localFile());

        $repository->add($media);

        $sut = $this->createSUT($repository, $storage);

        $sut(new DownloadMediaRequest($media->id()));
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository(), MediaStorageMother::inMemory());

        $sut(new DownloadMediaRequest(MediaId::generate()));
    }

    public function testMediaWithoutStoredContents(): void
    {
        $this->expectException(NotStoredMediaException::class);

        $repository = new InMemoryMediaRepository();

        $media = MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public);

        $repository->add($media);

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $sut(new DownloadMediaRequest($media->id()));
    }

    private function createSUT(InMemoryMediaRepository $repository, InMemoryMediaStorage $storage): DownloadPublicMediaService
    {
        return new DownloadPublicMediaService($repository, $storage);
    }
}
