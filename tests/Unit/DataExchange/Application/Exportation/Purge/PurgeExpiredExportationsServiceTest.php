<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation\Purge;

use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;
use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsService;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryExportationRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Exportation\SpyExportStorage;
use App\Tests\Unit\DataExchange\Domain\Model\Exportation\ExportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PurgeExpiredExportationsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testPurgesExpiredOnly(): void
    {
        $expired = ExportationMother::expiredWithFile();
        $valid = ExportationMother::validWithFile();

        $repository = new InMemoryExportationRepository();
        $repository->add($expired);
        $repository->add($valid);

        $storage = new SpyExportStorage();

        $sut = new PurgeExpiredExportationsService($repository, $storage);

        $response = $sut(new PurgeExpiredExportationsRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(1, $response->purged);
        $this->assertNull($repository->find($expired->id()));
        $this->assertNotNull($repository->find($valid->id()));
    }
}
