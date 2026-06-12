<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Dashboard;

use App\FoodLoss\Application\Surplus\Dashboard\GetSurplusDashboardRequest;
use App\FoodLoss\Application\Surplus\Dashboard\GetSurplusDashboardService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetSurplusDashboardServiceTest extends TestCase
{
    private const PROPERTY_ID = '00000000-0000-0000-0000-000000000010';
    private const CRITICAL_POINT_ID = '00000000-0000-0000-0000-000000000001';
    private const SURPLUS_CAUSE_ID = '00000000-0000-0000-0000-000000000001';
    private const SERVICE_CATEGORY_ID = '00000000-0000-0000-0000-000000000001';

    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');

        $surplusRepository = SurplusRepositoryMother::inMemory();

        $donated = SurplusMother::withAccountId($accountId);
        $donated->assessHumanDonation(true);
        $surplusRepository->add($donated);

        $eliminated = SurplusMother::withAccountId($accountId);
        $eliminated->assessElimination(true);
        $surplusRepository->add($eliminated);

        $surplusRepository->add(SurplusMother::withAccountId($accountId));
        $surplusRepository->add(SurplusMother::random());

        $sut = $this->createSUT($surplusRepository);

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(3, $response->totalSurpluses);
        $this->assertSame(15.0, $response->totalKg);
        $this->assertSame(0.0, $response->totalRations);
        $this->assertSame(DeliveryLogUnit::Kg, $response->displayUnit);
        $this->assertSame(15.0, $response->totalQuantity);
        $this->assertSame(1, $response->recoveredSurpluses);
        $this->assertSame(1, $response->eliminatedSurpluses);
        $this->assertSame(1, $response->unassessedSurpluses);
        $this->assertSame(33.3, $response->recoveredPercentage());

        $expectedDestinationColors = array_map(static fn(SurplusDestination $destination): string => $destination->color(), SurplusDestination::cases());
        $expectedDestinationColors[] = GetSurplusDashboardService::UNASSESSED_COLOR;
        $this->assertSame($expectedDestinationColors, $response->byDestination->colors);

        $destinationCounts = array_combine($response->byDestination->labels, $response->byDestination->values);
        $this->assertSame(1, $destinationCounts['HumanDonation']);
        $this->assertSame(1, $destinationCounts['Elimination']);
        $this->assertSame(1, $destinationCounts[GetSurplusDashboardService::UNASSESSED]);
        $this->assertSame(0, $destinationCounts['Transformation']);

        $this->assertSame(['Cocina'], $response->byCriticalPoint->labels);
        $this->assertSame([3], $response->byCriticalPoint->values);

        $this->assertSame(['Hotel Test'], $response->byProperty->labels);
        $this->assertSame([3], $response->byProperty->values);

        $this->assertSame(['Exceso de producción'], $response->bySurplusCause->labels);
        $this->assertSame(['Comida'], $response->byServiceCategory->labels);
        $this->assertSame(['vegetables'], $response->byFoodType->labels);

        $this->assertSame(['05/2026'], $response->quantityByMonth->labels);
        $this->assertSame([15.0], $response->quantityByMonth->values);

        $this->assertSame(3, $response->totalWasteManagers);
        $this->assertSame(1, $response->animalFeedAuthorizedWasteManagers);
        $this->assertSame(2, $response->compostAuthorizedWasteManagers);
    }

    public function testConvertsRationsToKgWithTheConversionFactor(): void
    {
        $accountId = new AccountId('account-id-test');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::from(id: (string) SurplusId::generate(), accountId: (string) $accountId, quantity: 10.0, unit: DeliveryLogUnit::Kg));
        $surplusRepository->add(SurplusMother::from(id: (string) SurplusId::generate(), accountId: (string) $accountId, quantity: 5.0, unit: DeliveryLogUnit::Rations));

        $sut = $this->createSUT($surplusRepository);

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(10.0, $response->totalKg);
        $this->assertSame(5.0, $response->totalRations);
        $this->assertSame(DeliveryLogUnit::Kg, $response->displayUnit);
        $this->assertSame(12.0, $response->totalQuantity);
        $this->assertSame([12.0], $response->quantityByMonth->values);
    }

    public function testConvertsKgToRationsWithACustomConversionFactor(): void
    {
        $accountId = new AccountId('account-id-test');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::from(id: (string) SurplusId::generate(), accountId: (string) $accountId, quantity: 10.0, unit: DeliveryLogUnit::Kg));
        $surplusRepository->add(SurplusMother::from(id: (string) SurplusId::generate(), accountId: (string) $accountId, quantity: 5.0, unit: DeliveryLogUnit::Rations));

        $sut = $this->createSUT($surplusRepository);

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $request->displayUnit = DeliveryLogUnit::Rations;
        $request->kgPerRation = 0.5;
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(DeliveryLogUnit::Rations, $response->displayUnit);
        $this->assertSame(25.0, $response->totalQuantity);
        $this->assertSame([25.0], $response->quantityByMonth->values);
    }

    public function testFallsBackToTheDefaultFactorWhenTheGivenOneIsNotPositive(): void
    {
        $accountId = new AccountId('account-id-test');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::from(id: (string) SurplusId::generate(), accountId: (string) $accountId, quantity: 10.0, unit: DeliveryLogUnit::Rations));

        $sut = $this->createSUT($surplusRepository);

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $request->kgPerRation = 0.0;
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(10.0 * DeliveryLogUnit::MITECO_KG_PER_RATION, $response->totalQuantity);
    }

    public function testFillsEmptyMonthsBetweenFirstAndLastSurplus(): void
    {
        $accountId = new AccountId('account-id-test');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-01-15')));
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-03-15')));

        $sut = $this->createSUT($surplusRepository);

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(['01/2026', '02/2026', '03/2026'], $response->quantityByMonth->labels);
        $this->assertSame([5.0, 0.0, 5.0], $response->quantityByMonth->values);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId('account-id-test');

        $sut = $this->createSUT(SurplusRepositoryMother::inMemory());

        $request = new GetSurplusDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(SurplusRepository $surplusRepository): GetSurplusDashboardService
    {
        return new GetSurplusDashboardService(
            repository: $surplusRepository,
            authorizer: new SurplusAuthorizer(),
            criticalPointRepository: $this->createCriticalPointRepository(),
            propertyRepository: $this->createPropertyRepository(),
            surplusCauseRepository: $this->createSurplusCauseRepository(),
            serviceCategoryRepository: $this->createServiceCategoryRepository(),
            wasteManagerRepository: $this->createWasteManagerRepository(),
        );
    }

    private function createWasteManagerRepository(): WasteManagerRepository
    {
        $repository = WasteManagerRepositoryMother::inMemory();
        $repository->add(WasteManagerMother::from(
            name: 'Gestor A',
            id: (string) WasteManagerId::generate(),
            animalFeedAuthorized: true,
            compostAuthorized: true,
        ));
        $repository->add(WasteManagerMother::from(
            name: 'Gestor B',
            id: (string) WasteManagerId::generate(),
            compostAuthorized: true,
        ));
        $repository->add(WasteManagerMother::from(
            name: 'Gestor C',
            id: (string) WasteManagerId::generate(),
        ));
        $repository->add(WasteManagerMother::fromAccount('another-account-id'));

        return $repository;
    }

    private function createCriticalPointRepository(): CriticalPointRepository
    {
        $repository = CriticalPointRepositoryMother::inMemory();
        $repository->add(CriticalPointMother::from(
            name: 'Cocina',
            id: self::CRITICAL_POINT_ID,
            accountId: new AccountId('account-id-test'),
            propertyId: new PropertyId(self::PROPERTY_ID),
        ));

        return $repository;
    }

    private function createPropertyRepository(): PropertyRepository
    {
        $repository = PropertyRepositoryMother::inMemory();
        $repository->add(PropertyMother::fromNameAndId('Hotel Test', self::PROPERTY_ID));

        return $repository;
    }

    private function createSurplusCauseRepository(): SurplusCauseRepository
    {
        $repository = SurplusCauseRepositoryMother::inMemory();
        $repository->add(SurplusCauseMother::fromNameAndId('Exceso de producción', self::SURPLUS_CAUSE_ID));

        return $repository;
    }

    private function createServiceCategoryRepository(): ServiceCategoryRepository
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();
        $repository->add(ServiceCategoryMother::fromNameAndId('Comida', self::SERVICE_CATEGORY_ID));

        return $repository;
    }
}
