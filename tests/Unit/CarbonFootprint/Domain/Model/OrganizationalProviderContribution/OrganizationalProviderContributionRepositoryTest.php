<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionFilter;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

abstract class OrganizationalProviderContributionRepositoryTest extends TestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $contribution = OrganizationalProviderContributionMother::manual();

        $this->assertNull($repository->find($contribution->id()));

        $repository->add($contribution);

        $this->assertSame($contribution, $repository->find($contribution->id()));
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();
        $contribution = OrganizationalProviderContributionMother::requested(token: str_repeat('a', 64));
        $repository->add($contribution);
        $repository->add(OrganizationalProviderContributionMother::manual());

        $this->assertSame($contribution, $repository->findByToken(str_repeat('a', 64)));
        $this->assertNull($repository->findByToken(''));
        $this->assertNull($repository->findByToken('unknown'));
    }

    public function testFindIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $contribution = OrganizationalProviderContributionMother::requested();
        $repository->add($contribution);

        $contribution->remove();
        $repository->add($contribution);

        $this->assertNull($repository->find($contribution->id()));
        $this->assertNull($repository->findByToken($contribution->token()));
        $this->assertSame([], $repository->findActiveByFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-id'));
    }

    public function testFindActiveByFootprint(): void
    {
        $repository = $this->createSUT();
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-1', providerId: 'provider-1'));
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-1', providerId: 'provider-2'));
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-2'));
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-1', subjectType: CarbonFootprintReportSubjectType::Company));

        $this->assertCount(2, $repository->findActiveByFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1'));
    }

    public function testFindActiveByFootprintAndProviderIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $removed = OrganizationalProviderContributionMother::manual(providerId: 'provider-1');
        $removed->remove();
        $repository->add($removed);

        $this->assertNull($repository->findActiveByFootprintAndProvider(CarbonFootprintReportSubjectType::Property, 'footprint-id', new ProviderId('provider-1')));

        $active = OrganizationalProviderContributionMother::manual(providerId: 'provider-1');
        $repository->add($active);

        $this->assertSame($active, $repository->findActiveByFootprintAndProvider(CarbonFootprintReportSubjectType::Property, 'footprint-id', new ProviderId('provider-1')));
        $this->assertNull($repository->findActiveByFootprintAndProvider(CarbonFootprintReportSubjectType::Property, 'footprint-id', new ProviderId('provider-2')));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $repository->add(OrganizationalProviderContributionMother::manual(providerId: 'provider-1'));
        $repository->add(OrganizationalProviderContributionMother::requested(providerId: 'provider-2'));
        $repository->add(OrganizationalProviderContributionMother::requested(providerId: 'provider-3', accountId: 'other-account'));

        $filter = new OrganizationalProviderContributionFilter(mode: ProviderContributionMode::Requested, limit: 0);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
        $this->assertCount(1, $repository->findByFilter(new OrganizationalProviderContributionFilter(limit: 1)));
        $this->assertSame(3, $repository->totalResultsByFilter(new OrganizationalProviderContributionFilter(limit: 1)));
    }

    public function testFindByFilterBySubjectAndFootprint(): void
    {
        $repository = $this->createSUT();
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-a', providerId: 'provider-1'));
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-b', providerId: 'provider-2'));
        $repository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: 'footprint-a', providerId: 'provider-3', subjectType: CarbonFootprintReportSubjectType::Company));

        $filter = new OrganizationalProviderContributionFilter(subjectType: CarbonFootprintReportSubjectType::Property, carbonFootprintId: 'footprint-a', limit: 0);

        $this->assertCount(1, $repository->findByFilter($filter));
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter(new OrganizationalProviderContributionFilter(carbonFootprintId: 'footprint-a', limit: 0)));
        $this->assertSame(1, $repository->totalResultsByFilter(new OrganizationalProviderContributionFilter(subjectType: CarbonFootprintReportSubjectType::Company, limit: 0)));
    }

    public function testFindByFilterByAccount(): void
    {
        $repository = $this->createSUT();
        $repository->add(OrganizationalProviderContributionMother::manual(providerId: 'provider-1'));
        $repository->add(OrganizationalProviderContributionMother::manual(providerId: 'provider-2', accountId: 'other-account'));

        $filter = new OrganizationalProviderContributionFilter(accountId: new AccountId('other-account'), limit: 0);

        $this->assertCount(1, $repository->findByFilter($filter));
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();
        $ignored = OrganizationalProviderContributionMother::requested(providerId: 'provider-1');
        $ignored->ignore();
        $repository->add($ignored);
        $repository->add(OrganizationalProviderContributionMother::requested(providerId: 'provider-2'));

        $filter = new OrganizationalProviderContributionFilter(status: ProviderContributionStatus::Ignored, limit: 0);

        $this->assertSame([$ignored], $repository->findByFilter($filter));
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): OrganizationalProviderContributionRepository;
}
