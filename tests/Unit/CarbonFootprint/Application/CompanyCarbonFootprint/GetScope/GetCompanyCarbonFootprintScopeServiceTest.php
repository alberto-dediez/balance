<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\RefrigerantGasCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetCompanyCarbonFootprintScopeServiceTest extends TestCase
{
    private \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository $repository;
    private GetCompanyCarbonFootprintScopeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $catalogProviders = new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
            new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory()),
            new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
        ]);

        $this->sut = new GetCompanyCarbonFootprintScopeService(
            $this->repository,
            PropertyRepositoryMother::inMemory(),
            new CompanyCarbonFootprintAuthorizer(),
            new StageSectionsBuilder($catalogProviders, ActivityRepositoryMother::inMemory()),
        );
    }

    public function testScope1ReturnsItsThreeSections(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(1, $response->blocks);
        $this->assertCount(3, $response->blocks[0]->sections);
        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], array_map(static fn($s) => $s->definition->key, $response->blocks[0]->sections));
        $this->assertSame(ActivityType::RefrigerantGas, $response->blocks[0]->sections[2]->definition->activityType);
    }

    public function testScope2ReturnsOnlyElectricity(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope2), $this->ownerUser());

        $this->assertCount(1, $response->blocks[0]->sections);
        $this->assertSame('electricity', $response->blocks[0]->sections[0]->definition->key);
    }

    public function testExclusionsAreIndexedBySource(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertArrayHasKey(Source::RefrigerantLeaks->value, $response->exclusions);
        $this->assertSame('Sin datos', $response->exclusions[Source::RefrigerantLeaks->value]->comment());
    }

    public function testUnknownFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetCompanyCarbonFootprintScopeRequest(CompanyCarbonFootprintId::generate(), Scope::Scope1), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): \App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), $accountId);
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
