<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove\RemovePropertyCarbonFootprintCertificatesRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove\RemovePropertyCarbonFootprintCertificatesService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRemoved;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryPropertyCarbonFootprintCertificateStorage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemovePropertyCarbonFootprintCertificatesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testEveryCertificateOfTheFootprintAndItsFilesAreRemoved(): void
    {
        $propertyCarbonFootprintId = PropertyCarbonFootprintId::generate();
        $certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $storage = new InMemoryPropertyCarbonFootprintCertificateStorage();

        $revoked = PropertyCarbonFootprintCertificateMother::revoked(propertyCarbonFootprintId: $propertyCarbonFootprintId);
        $current = PropertyCarbonFootprintCertificateMother::issued(propertyCarbonFootprintId: $propertyCarbonFootprintId);
        $current->markGenerated($this->storeFiles($storage, $current->id()));
        $other = PropertyCarbonFootprintCertificateMother::generated();

        $certificates->add($revoked);
        $certificates->add($current);
        $certificates->add($other);

        $spy = new SpyDomainEventSubscriber();

        (new RemovePropertyCarbonFootprintCertificatesService($certificates, $storage))(
            new RemovePropertyCarbonFootprintCertificatesRequest($propertyCarbonFootprintId),
            UserMother::systemUser(),
        );

        $this->assertSame([], $certificates->findByPropertyCarbonFootprintId($propertyCarbonFootprintId));
        $this->assertNotNull($certificates->find($other->id()));
        $this->assertSame([], $storage->paths());
        $this->assertContains('account/certificate-es.pdf', $storage->deleted());
        $this->assertInstanceOf(PropertyCarbonFootprintCertificateRemoved::class, $spy->lastEvent());
    }

    /** @return array<string, string> */
    private function storeFiles(InMemoryPropertyCarbonFootprintCertificateStorage $storage, PropertyCarbonFootprintCertificateId $id): array
    {
        $files = [];

        foreach ([Locale::Spanish, Locale::English] as $locale) {
            $localPath = sprintf('%s/%s-%s.pdf', sys_get_temp_dir(), (string) $id, $locale->value);
            file_put_contents($localPath, 'pdf');

            $files[$locale->value] = $storage->store($id, null, $locale, $localPath);

            @unlink($localPath);
        }

        return $files;
    }
}
