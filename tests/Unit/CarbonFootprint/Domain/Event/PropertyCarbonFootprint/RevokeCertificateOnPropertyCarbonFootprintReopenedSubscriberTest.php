<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke\RevokePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RevokeCertificateOnPropertyCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RevokeCertificateOnPropertyCarbonFootprintReopenedSubscriberTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToReopened(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintReopened(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testRevokesTheCurrentCertificate(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $certificate = PropertyCarbonFootprintCertificateMother::generated(propertyCarbonFootprintId: $id);
        $other = PropertyCarbonFootprintCertificateMother::generated();
        $this->certificates->add($certificate);
        $this->certificates->add($other);

        $this->createSUT()->handle(new PropertyCarbonFootprintReopened($id));

        $this->assertTrue($certificate->isRevoked());
        $this->assertFalse($other->isRevoked());
        $this->assertNull($this->certificates->findCurrentByPropertyCarbonFootprintId($id));
    }

    public function testDoesNothingWithoutCertificate(): void
    {
        $id = PropertyCarbonFootprintId::generate();

        $this->createSUT()->handle(new PropertyCarbonFootprintReopened($id));

        $this->assertSame([], $this->certificates->findByPropertyCarbonFootprintId($id));
    }

    private function createSUT(): RevokeCertificateOnPropertyCarbonFootprintReopenedSubscriber
    {
        return new RevokeCertificateOnPropertyCarbonFootprintReopenedSubscriber(
            new RevokePropertyCarbonFootprintCertificateService($this->certificates),
            $this->certificates,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
