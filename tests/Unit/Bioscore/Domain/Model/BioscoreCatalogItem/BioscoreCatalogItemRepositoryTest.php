<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemFilter;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BioscoreCatalogItemRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $item = BioscoreCatalogItemMother::random();

        $this->assertNull($repository->find($item->id()));

        $repository->add($item);

        $recovered = $repository->find($item->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($item->id(), $recovered->id());
    }

    public function testFindByCatalogAndBioscoreId(): void
    {
        $repository = $this->createSUT();

        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 7));
        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::TransportFuel, bioscoreId: 7));

        $facilityFuel = $repository->findByCatalogAndBioscoreId(BioscoreCatalog::FacilityFuel, 7);
        $transportFuel = $repository->findByCatalogAndBioscoreId(BioscoreCatalog::TransportFuel, 7);

        $this->assertNotNull($facilityFuel);
        $this->assertNotNull($transportFuel);
        $this->assertNotEquals((string) $facilityFuel->id(), (string) $transportFuel->id());
        $this->assertNull($repository->findByCatalogAndBioscoreId(BioscoreCatalog::RefrigerantGas, 7));
    }

    public function testFindByFilterByCatalog(): void
    {
        $repository = $this->createSUT();

        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 1));
        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 2));
        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::TransportFuel, bioscoreId: 3));

        $this->assertCount(2, $repository->findByFilter(new BioscoreCatalogItemFilter(catalog: BioscoreCatalog::FacilityFuel, limit: 0)));
        $this->assertSame(2, $repository->totalResultsByFilter(new BioscoreCatalogItemFilter(catalog: BioscoreCatalog::FacilityFuel)));
    }

    public function testFindByFilterByStatusWithdrawnNameAndTarget(): void
    {
        $repository = $this->createSUT();

        $repository->add(BioscoreCatalogItemMother::registered(bioscoreId: 1, name: 'Gas natural'));
        $repository->add(BioscoreCatalogItemMother::mapped(targetId: 'target-1', automatic: true, bioscoreId: 2, name: 'Gasóleo C'));
        $repository->add(BioscoreCatalogItemMother::withdrawn(bioscoreId: 3, name: 'Propano'));

        $this->assertCount(1, $repository->findByFilter(new BioscoreCatalogItemFilter(status: BioscoreMappingStatus::AutoMapped, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new BioscoreCatalogItemFilter(withdrawn: true, limit: 0)));
        $this->assertCount(2, $repository->findByFilter(new BioscoreCatalogItemFilter(withdrawn: false, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new BioscoreCatalogItemFilter(name: 'Propano', limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new BioscoreCatalogItemFilter(targetId: 'target-1', limit: 0)));
    }

    public function testFindByFilterWithLimitZeroReturnsAllElements(): void
    {
        $repository = $this->createSUT();

        for ($i = 1; $i <= 12; ++$i) {
            $repository->add(BioscoreCatalogItemMother::registered(bioscoreId: $i, name: 'Item ' . $i));
        }

        $this->assertCount(12, $repository->findByFilter(new BioscoreCatalogItemFilter(limit: 0)));
        $this->assertCount(10, $repository->findByFilter(new BioscoreCatalogItemFilter()));
    }

    public function testCountPendingExcludesWithdrawnIgnoredAndOtherCatalogs(): void
    {
        $repository = $this->createSUT();

        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 1));
        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 2));
        $repository->add(BioscoreCatalogItemMother::ignored(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 3));
        $repository->add(BioscoreCatalogItemMother::withdrawn(catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 4));
        $repository->add(BioscoreCatalogItemMother::mapped(targetId: 'target-1', catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 5));
        $repository->add(BioscoreCatalogItemMother::registered(catalog: BioscoreCatalog::TransportFuel, bioscoreId: 6));

        $this->assertSame(2, $repository->countPending(BioscoreCatalog::FacilityFuel));
        $this->assertSame(1, $repository->countPending(BioscoreCatalog::TransportFuel));
    }

    abstract protected function createSUT(): BioscoreCatalogItemRepository;
}
