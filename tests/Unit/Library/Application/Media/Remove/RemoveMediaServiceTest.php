<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Remove;

use App\Library\Application\Media\Remove\RemoveMediaRequest;
use App\Library\Application\Media\Remove\RemoveMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRemoved;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMediaServiceTest extends TestCase
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

        $media = MediaMother::uploadedMedia();

        $storage->store($media->id(), null, 'png', MediaStorageMother::localFile());

        $repository->add($media);

        $this->assertTrue($storage->exists($media->path()));

        $sut = $this->createSUT($repository, $storage);

        $result = $sut(new RemoveMediaRequest($media->id()), UserMother::adminUser());

        $this->assertTrue($result->mediaId->equals($media->id()));
        $this->assertNull($repository->find($media->id()));
        $this->assertFalse($storage->exists($media->path()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof MediaRemoved);
    }

    public function testUnknownMedia(): void
    {
        $this->expectException(MediaNotFoundException::class);

        $sut = $this->createSUT(new InMemoryMediaRepository(), MediaStorageMother::inMemory());

        $sut(new RemoveMediaRequest(MediaId::generate()), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryMediaRepository();

        $media = MediaMother::fromNameAndAccount('logo.png', AccountId::generate());

        $repository->add($media);

        $sut = $this->createSUT($repository, MediaStorageMother::inMemory());

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Owner, AccountId::generate());

        $sut(new RemoveMediaRequest($media->id()), $foreignUser);
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(new InMemoryMediaRepository(), MediaStorageMother::inMemory());

        $this->assertTrue($sut->isHandled(new RemoveMediaRequest(MediaId::generate())));
    }

    private function createSUT(InMemoryMediaRepository $repository, InMemoryMediaStorage $storage): RemoveMediaService
    {
        return new RemoveMediaService($repository, $storage, MediaAuthorizerMother::create());
    }
}
