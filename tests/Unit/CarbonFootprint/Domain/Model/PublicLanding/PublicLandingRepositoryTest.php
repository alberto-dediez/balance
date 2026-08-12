<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Property\Domain\Model\Property\PropertyId;
use PHPUnit\Framework\TestCase;

abstract class PublicLandingRepositoryTest extends TestCase
{
    public function testAddPublicLandingToRepository(): void
    {
        $repository = $this->createSUT();

        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $this->assertNull($repository->find($publicLanding->id()));

        $repository->add($publicLanding);

        $recovered = $repository->find($publicLanding->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($publicLanding->id(), $recovered->id());
    }

    public function testFindByOwner(): void
    {
        $repository = $this->createSUT();

        $propertyId = (string) PropertyId::generate();
        $publicLanding = PublicLandingMother::forProperty($propertyId);

        $repository->add($publicLanding);

        $recovered = $repository->findByOwner(PublicLandingOwnerType::Property, $propertyId);

        $this->assertNotNull($recovered);
        $this->assertEquals($publicLanding->id(), $recovered->id());
    }

    public function testFindByOwnerTellsEventsAndPropertiesApart(): void
    {
        $repository = $this->createSUT();

        $ownerId = (string) EventId::generate();
        $repository->add(PublicLandingMother::forEvent($ownerId));

        $this->assertNotNull($repository->findByOwner(PublicLandingOwnerType::Event, $ownerId));
        $this->assertNull($repository->findByOwner(PublicLandingOwnerType::Property, $ownerId));
    }

    public function testFindByOwnerReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByOwner(PublicLandingOwnerType::Property, (string) PropertyId::generate()));
    }

    abstract protected function createSUT(): PublicLandingRepository;
}
