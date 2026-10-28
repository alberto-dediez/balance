<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions\CalculateOrganizationalProviderContributionActivityEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions\CalculateOrganizationalProviderContributionActivityEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPurchaseServiceRepository;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionPublicServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;

class CalculateOrganizationalProviderContributionActivityEmissionsServiceTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    private CalculateOrganizationalProviderContributionActivityEmissionsService $sut;
    private InMemoryPurchaseServiceRepository $purchaseServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseServiceRepository = PurchaseServiceRepositoryMother::inMemory();
        $this->sut = new CalculateOrganizationalProviderContributionActivityEmissionsService(
            $this->authenticator,
            new ActivityEmissionsResolver(
                new EmissionFactorSelectorRegistry([
                    new ReportedEmissionsEmissionFactorSelector(),
                    new PurchaseServiceEmissionFactorSelector($this->purchaseServiceRepository),
                ]),
                EmissionsCalculatorMother::create(),
            ),
        );
    }

    public function testCalculatesEmissions(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $response = ($this->sut)($this->request($contribution->token()));

        $this->assertEqualsWithDelta(1200.0, (float) $response->emissions->co2eq(), 0.001);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        ($this->sut)($this->request('invalid-token'));
    }

    public function testIgnoredContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());
        $contribution->ignore();

        $this->expectException(OrganizationalProviderContributionIgnoredException::class);

        ($this->sut)($this->request($contribution->token()));
    }

    public function testMissingExchangeRateThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());
        $groupId = $footprint->emissionFactorGroupId();
        $this->assertNotNull($groupId);
        $purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $purchaseService->setEmissionFactor(new EmissionFactor('0.5', '0.5', '0', '0', $footprint->calculationYear(), $groupId, Unit::Euro));
        $this->purchaseServiceRepository->add($purchaseService);

        $this->expectException(ExchangeRateNotFoundException::class);

        ($this->sut)(new CalculateOrganizationalProviderContributionActivityEmissionsRequest(
            token: $contribution->token(),
            activityType: ActivityType::PurchaseService,
            itemId: (string) $purchaseService->id(),
            condition: null,
            quantity: new Quantity(250.0, Unit::UsDollar),
        ));
    }

    private function request(string $token): CalculateOrganizationalProviderContributionActivityEmissionsRequest
    {
        return new CalculateOrganizationalProviderContributionActivityEmissionsRequest(
            token: $token,
            activityType: ActivityType::ReportedEmissions,
            itemId: '',
            condition: null,
            quantity: new Quantity(1.2, Unit::Tonne),
        );
    }
}
