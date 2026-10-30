<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionFilter;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

abstract class OperatorContributionRepositoryTest extends TestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $contribution = OperatorContributionMother::pending();

        $this->assertNull($repository->find($contribution->id()));

        $repository->add($contribution);

        $this->assertSame($contribution, $repository->find($contribution->id()));
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();
        $contribution = OperatorContributionMother::pending(token: str_repeat('a', 64));
        $repository->add($contribution);
        $repository->add(OperatorContributionMother::pending());

        $this->assertSame($contribution, $repository->findByToken(str_repeat('a', 64)));
        $this->assertNull($repository->findByToken(''));
        $this->assertNull($repository->findByToken('unknown'));
    }

    public function testFindIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $contribution = OperatorContributionMother::pending();
        $repository->add($contribution);

        $contribution->remove();
        $repository->add($contribution);

        $this->assertNull($repository->find($contribution->id()));
        $this->assertNull($repository->findByToken($contribution->token()));
        $this->assertSame([], $repository->findActiveByCompanyCarbonFootprint(new CompanyCarbonFootprintId('company-footprint-id')));
    }

    public function testFindActiveByCompanyCarbonFootprint(): void
    {
        $repository = $this->createSUT();
        $repository->add(OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-1'));
        $repository->add(OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-2'));
        $repository->add(OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-2', propertyId: 'property-1'));

        $this->assertCount(2, $repository->findActiveByCompanyCarbonFootprint(new CompanyCarbonFootprintId('footprint-1')));
        $this->assertCount(1, $repository->findActiveByCompanyCarbonFootprint(new CompanyCarbonFootprintId('footprint-2')));
    }

    public function testFindActiveByCompanyCarbonFootprintAndPropertyIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $removed = OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-1');
        $removed->remove();
        $repository->add($removed);

        $this->assertNull($repository->findActiveByCompanyCarbonFootprintAndProperty(new CompanyCarbonFootprintId('footprint-1'), new PropertyId('property-1')));

        $active = OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-1');
        $repository->add($active);
        $repository->add(OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-2'));

        $this->assertSame($active, $repository->findActiveByCompanyCarbonFootprintAndProperty(new CompanyCarbonFootprintId('footprint-1'), new PropertyId('property-1')));
        $this->assertNull($repository->findActiveByCompanyCarbonFootprintAndProperty(new CompanyCarbonFootprintId('footprint-2'), new PropertyId('property-1')));
    }

    public function testFindLatestByAccountAndPropertyIncludesRemoved(): void
    {
        $repository = $this->createSUT();
        $older = new \ReflectionProperty(OperatorContribution::class, 'registeredAt');

        $first = OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-1', propertyId: 'property-1', accountId: 'account-1');
        $older->setValue($first, new \DateTimeImmutable('2024-01-01'));
        $repository->add($first);

        $latest = OperatorContributionMother::pending(companyCarbonFootprintId: 'footprint-2', propertyId: 'property-1', accountId: 'account-1');
        $latest->remove();
        $repository->add($latest);

        $this->assertSame($latest, $repository->findLatestByAccountAndProperty(new AccountId('account-1'), new PropertyId('property-1')));
        $this->assertNull($repository->findLatestByAccountAndProperty(new AccountId('account-2'), new PropertyId('property-1')));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $repository->add(OperatorContributionMother::pending(accountId: 'account-1', propertyId: 'property-1'));
        $repository->add(OperatorContributionMother::answered(accountId: 'account-1', propertyId: 'property-2'));
        $repository->add(OperatorContributionMother::ignored(accountId: 'account-2', propertyId: 'property-3'));

        $this->assertCount(2, $repository->findByFilter(new OperatorContributionFilter(accountId: new AccountId('account-1'))));
        $this->assertCount(1, $repository->findByFilter(new OperatorContributionFilter(status: ProviderContributionStatus::Answered)));
        $this->assertCount(1, $repository->findByFilter(new OperatorContributionFilter(propertyId: new PropertyId('property-3'))));
        $this->assertSame(3, $repository->totalResultsByFilter(new OperatorContributionFilter(limit: 1)));
        $this->assertCount(1, $repository->findByFilter(new OperatorContributionFilter(limit: 1)));
    }

    abstract protected function createSUT(): OperatorContributionRepository;
}
