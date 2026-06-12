<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\CriticalPoint\Dashboard;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\Dashboard\GetCriticalPointDashboardRequest;
use App\FoodLoss\Application\CriticalPoint\Dashboard\GetCriticalPointDashboardService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetCriticalPointDashboardServiceTest extends TestCase
{
    private const PROPERTY_ID = '00000000-0000-0000-0000-000000000010';
    private const OTHER_PROPERTY_ID = '00000000-0000-0000-0000-000000000020';
    private const SERVICE_CATEGORY_ID = '00000000-0000-0000-0000-000000000001';

    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::withAssessments(
            accountId: $accountId,
            supplyChainStage: SupplyChainStage::Preparation,
            preventionPossible: true,
            transformationPossible: true,
            propertyId: new PropertyId(self::PROPERTY_ID),
            serviceCategoryIds: [new ServiceCategoryId(self::SERVICE_CATEGORY_ID)],
        ));
        $criticalPointRepository->add(CriticalPointMother::withAssessments(
            accountId: $accountId,
            supplyChainStage: SupplyChainStage::Preparation,
            propertyId: new PropertyId(self::PROPERTY_ID),
        ));
        $criticalPointRepository->add(CriticalPointMother::withAssessments(
            accountId: $accountId,
            supplyChainStage: SupplyChainStage::Service,
            humanDonationPossible: true,
            propertyId: new PropertyId(self::OTHER_PROPERTY_ID),
        ));
        $criticalPointRepository->add(CriticalPointMother::random());

        $sut = $this->createSUT($criticalPointRepository);

        $request = new GetCriticalPointDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(3, $response->totalCriticalPoints);
        $this->assertSame(1, $response->preventionPossibleCriticalPoints);
        $this->assertSame(33.3, $response->preventionPossiblePercentage());
        $this->assertSame(1, $response->eliminationOnlyCriticalPoints);
        $this->assertSame(2, $response->coveredProperties);

        $stageCounts = array_combine($response->bySupplyChainStage->labels, $response->bySupplyChainStage->values);
        $this->assertSame(0, $stageCounts['Receiving']);
        $this->assertSame(0, $stageCounts['Storage']);
        $this->assertSame(2, $stageCounts['Preparation']);
        $this->assertSame(1, $stageCounts['Service']);
        $this->assertSame(0, $stageCounts['PlateWaste']);

        $priorityCounts = array_combine($response->byPriority->labels, $response->byPriority->values);
        $this->assertSame(1, $priorityCounts['Prevention']);
        $this->assertSame(1, $priorityCounts['Transformation']);
        $this->assertSame(1, $priorityCounts['HumanDonation']);
        $this->assertSame(0, $priorityCounts['AnimalFeed']);
        $this->assertSame(0, $priorityCounts['Composting']);

        $expectedPriorityColors = [
            GetCriticalPointDashboardService::DEFAULT_PRIORITY_COLOR,
            SurplusDestination::Transformation->color(),
            SurplusDestination::HumanDonation->color(),
            SurplusDestination::AnimalFeed->color(),
            SurplusDestination::Composting->color(),
        ];
        $this->assertSame($expectedPriorityColors, $response->byPriority->colors);

        $this->assertSame(['Hotel Test', 'Hotel Two'], $response->byProperty->labels);
        $this->assertSame([2, 1], $response->byProperty->values);

        $this->assertSame(['Comida'], $response->byServiceCategory->labels);
        $this->assertSame([1], $response->byServiceCategory->values);
    }

    public function testFiltersByProperty(): void
    {
        $accountId = new AccountId('account-id-test');

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::withAssessments(
            accountId: $accountId,
            propertyId: new PropertyId(self::PROPERTY_ID),
        ));
        $criticalPointRepository->add(CriticalPointMother::withAssessments(
            accountId: $accountId,
            propertyId: new PropertyId(self::OTHER_PROPERTY_ID),
        ));

        $sut = $this->createSUT($criticalPointRepository);

        $request = new GetCriticalPointDashboardRequest(accountId: $accountId, propertyId: new PropertyId(self::PROPERTY_ID));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(1, $response->totalCriticalPoints);
        $this->assertSame(['Hotel Test'], $response->byProperty->labels);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId('account-id-test');

        $sut = $this->createSUT(CriticalPointRepositoryMother::inMemory());

        $request = new GetCriticalPointDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(CriticalPointRepository $criticalPointRepository): GetCriticalPointDashboardService
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Test', self::PROPERTY_ID));
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Two', self::OTHER_PROPERTY_ID));

        $serviceCategoryRepository = ServiceCategoryRepositoryMother::inMemory();
        $serviceCategoryRepository->add(ServiceCategoryMother::fromNameAndId('Comida', self::SERVICE_CATEGORY_ID));

        return new GetCriticalPointDashboardService(
            repository: $criticalPointRepository,
            authorizer: new CriticalPointAuthorizer(),
            propertyRepository: $propertyRepository,
            serviceCategoryRepository: $serviceCategoryRepository,
        );
    }
}
