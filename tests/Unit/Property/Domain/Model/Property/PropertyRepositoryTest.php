<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PropertyRepositoryTest extends KernelTestCase
{
    public function testAddPropertyToRepository(): void
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $this->assertNull($repository->find($property->id()));

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());
    }

    public function testRemovePropertyToRepository(): void
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());

        $repository->remove($property);

        $this->assertNull($repository->find($property->id()));
    }

    public function testFindByName(): void
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $this->assertNull($repository->findByName($property->name()));

        $repository->add($property);

        $recoverProperty = $repository->findByName($property->name());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromName('Hotel'));

        $filter = new PropertyFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PropertyFilter(
            name: 'Hotel',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithOperatingRegime(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', CompanyId::generate(), PropertyId::generate(), OperatingRegime::Franchised));

        $result = $repository->findByFilter(new PropertyFilter(operatingRegime: OperatingRegime::Franchised));

        $this->assertCount(1, $result);
        $this->assertSame('Franquicia', $result[0]->name());
    }

    public function testFindByFilterWithSeveralAccounts(): void
    {
        $repository = $this->createSUT();

        $firstAccountId = AccountId::generate();
        $secondAccountId = AccountId::generate();

        $repository->add(PropertyMother::from(PropertyId::generate(), 'First', $firstAccountId));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Second', $secondAccountId));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Other', AccountId::generate()));

        $result = $repository->findByFilter(new PropertyFilter(accountIds: [$firstAccountId, $secondAccountId], limit: 0));

        $names = array_map(static fn($property): string => (string) $property->name(), $result);
        sort($names);

        $this->assertSame(['First', 'Second'], $names);
        $this->assertSame(2, $repository->totalResultsByFilter(new PropertyFilter(accountIds: [$firstAccountId, $secondAccountId])));
    }

    public function testFindByFilterWithAnEmptyListOfAccountsReturnsNothing(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::from(PropertyId::generate(), 'First', AccountId::generate()));

        $this->assertSame([], $repository->findByFilter(new PropertyFilter(accountIds: [], limit: 0)));
        $this->assertSame(0, $repository->totalResultsByFilter(new PropertyFilter(accountIds: [])));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromName('Hotel'));

        $filter = new PropertyFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PropertyFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);

        $recoverProperty->remove();

        $repository->add($property);

        $this->assertNull($repository->find($property->id()));
    }

    public function testTotalResultsByFilterIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());

        $filter = new PropertyFilter(
            limit: 2,
        );

        $this->assertCount(2, $repository->findByFilter($filter));

        $this->assertEquals(6, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): PropertyRepository;
}
