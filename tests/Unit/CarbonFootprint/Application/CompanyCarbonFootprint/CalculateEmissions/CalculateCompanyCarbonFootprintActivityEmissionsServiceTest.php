<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CalculateEmissions\CalculateCompanyCarbonFootprintActivityEmissionsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CalculateEmissions\CalculateCompanyCarbonFootprintActivityEmissionsService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CalculateCompanyCarbonFootprintActivityEmissionsServiceTest extends TestCase
{
    private const YEAR = 2025;

    private \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository $repository;
    private \App\CarbonFootprint\Domain\Model\Combustible\Combustible $combustible;
    private CalculateCompanyCarbonFootprintActivityEmissionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $this->combustible = CombustibleMother::fromName('Gas natural');
        $this->combustible->setEmissionFactor(new EmissionFactor(
            co2eq: '0.18',
            co2: '0.18',
            ch4: '0',
            n2o: '0',
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: Unit::KWh,
        ));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->sut = new CalculateCompanyCarbonFootprintActivityEmissionsService(
            $this->repository,
            new CompanyCarbonFootprintAuthorizer(),
            new ActivityEmissionsResolver(
                new EmissionFactorSelectorRegistry([new CombustibleEmissionFactorSelector($combustibleRepository)]),
                new EmissionsCalculator(),
            ),
        );
    }

    public function testCalculatesWithTheFootprintYearAndGroup(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new CalculateCompanyCarbonFootprintActivityEmissionsRequest(
            $footprint->id(),
            ActivityType::Combustible,
            (string) $this->combustible->id(),
            null,
            new Quantity(1000.0, Unit::KWh),
        ), $this->ownerUser());

        $this->assertSame(180.0, (float) $response->emissions->co2eq());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new CalculateCompanyCarbonFootprintActivityEmissionsRequest(
            $footprint->id(),
            ActivityType::Combustible,
            (string) $this->combustible->id(),
            null,
            new Quantity(1000.0, Unit::KWh),
        ), $this->ownerUser());
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from(
            (string) CompanyId::generate(),
            (string) CompanyCarbonFootprintId::generate(),
            $accountId,
            self::YEAR,
        );
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
