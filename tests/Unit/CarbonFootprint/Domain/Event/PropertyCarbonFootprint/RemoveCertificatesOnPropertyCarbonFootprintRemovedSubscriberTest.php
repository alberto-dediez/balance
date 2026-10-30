<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove\RemovePropertyCarbonFootprintCertificatesService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryPropertyCarbonFootprintCertificateStorage;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;
    private InMemoryPropertyCarbonFootprintCertificateStorage $storage;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $this->storage = new InMemoryPropertyCarbonFootprintCertificateStorage();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRemoved(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintReopened(PropertyCarbonFootprintId::generate())));
    }

    public function testRemovesEveryCertificateOfTheFootprint(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $other = PropertyCarbonFootprintCertificateMother::generated();
        $this->certificates->add(PropertyCarbonFootprintCertificateMother::revoked(propertyCarbonFootprintId: $id));
        $this->certificates->add(PropertyCarbonFootprintCertificateMother::generated(propertyCarbonFootprintId: $id));
        $this->certificates->add($other);

        $this->createSUT()->handle(new PropertyCarbonFootprintRemoved($id));

        $this->assertSame([], $this->certificates->findByPropertyCarbonFootprintId($id));
        $this->assertNotNull($this->certificates->find($other->id()));
        $this->assertCount(2, $this->storage->deleted());
    }

    public function testDoesNotRequireASystemActorWithoutCertificates(): void
    {
        $sut = new RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriber(
            new RemovePropertyCarbonFootprintCertificatesService($this->certificates, $this->storage),
            $this->certificates,
            new SystemActorResolver(UserRepositoryMother::inMemory()),
        );

        $sut->handle(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate()));

        $this->assertSame([], $this->storage->deleted());
    }

    private function createSUT(): RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriber
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::adminUser());

        return new RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriber(
            new RemovePropertyCarbonFootprintCertificatesService($this->certificates, $this->storage),
            $this->certificates,
            new SystemActorResolver($users),
        );
    }
}
