<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Bioscore\Infrastructure\Gateway\InMemoryBioscoreConsumptionGateway;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptionsMother;
use PHPUnit\Framework\TestCase;

class InMemoryBioscoreConsumptionGatewayTest extends TestCase
{
    private InMemoryBioscoreConsumptionGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = new InMemoryBioscoreConsumptionGateway();
    }

    public function testReturnsOnlyTheRequestedProperties(): void
    {
        $this->gateway->set(BioscorePropertyConsumptionsMother::forProperty('property-1'));
        $this->gateway->set(BioscorePropertyConsumptionsMother::forProperty('property-2'));

        $results = $this->gateway->fetch($this->query(['property-2', 'property-3']));

        $this->assertCount(1, $results);
        $this->assertSame('property-2', (string) $results[0]->propertyId);
    }

    public function testAddAppendsConsumptionsToTheRightGroup(): void
    {
        $propertyId = new PropertyId('property-1');

        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas natural', 1000.0), bioscoreId: 5, name: 'Hotel', year: 2025);
        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::transportFuel(8, 'Gasolina', 200.0));
        $this->gateway->add($propertyId, 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));
        $this->gateway->add($propertyId, 'electricity', BioscoreConsumptionMother::electricity(3, 'Endesa', 10000.0));

        $results = $this->gateway->fetch($this->query(['property-1']));

        $this->assertCount(1, $results);
        $this->assertSame(5, $results[0]->bioscoreId);
        $this->assertSame('Hotel', $results[0]->name);
        $this->assertSame(2025, $results[0]->year);
        $this->assertCount(2, $results[0]->combustibles);
        $this->assertCount(1, $results[0]->refrigerantGases);
        $this->assertCount(1, $results[0]->electricity);
    }

    public function testAddRejectsAnUnknownGroup(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->gateway->add(new PropertyId('property-1'), 'invoices', BioscoreConsumptionMother::electricity(3, 'Endesa', 1.0));
    }

    public function testRecordsTheQueries(): void
    {
        $this->assertNull($this->gateway->lastQuery());

        $this->gateway->fetch($this->query(['property-1']));
        $this->gateway->fetch($this->query(['property-2']));

        $this->assertCount(2, $this->gateway->queries());
        $this->assertSame('property-2', (string) $this->gateway->lastQuery()?->propertyIds[0]);
    }

    public function testFailThrowsOnFetch(): void
    {
        $this->gateway->fail();

        $this->expectException(BioscoreUnavailableException::class);

        $this->gateway->fetch($this->query(['property-1']));
    }

    public function testFailFromASpecificQuery(): void
    {
        $this->gateway->fail(fromQuery: 2);

        $this->gateway->fetch($this->query(['property-1']));

        $this->expectException(BioscoreUnavailableException::class);

        $this->gateway->fetch($this->query(['property-1']));
    }

    public function testResetClearsEverything(): void
    {
        $this->gateway->set(BioscorePropertyConsumptionsMother::forProperty('property-1'));
        $this->gateway->fail();

        $this->gateway->reset();

        $this->assertSame([], $this->gateway->fetch($this->query(['property-1'])));
        $this->assertCount(1, $this->gateway->queries());
    }

    /** @param list<string> $propertyIds */
    private function query(array $propertyIds): BioscoreConsumptionsQuery
    {
        return new BioscoreConsumptionsQuery(
            new AccountId('account-id'),
            array_map(static fn(string $id): PropertyId => new PropertyId($id), $propertyIds),
            2025,
        );
    }
}
