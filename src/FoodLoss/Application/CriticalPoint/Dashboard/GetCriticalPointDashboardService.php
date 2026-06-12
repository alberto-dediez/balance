<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Dashboard;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetCriticalPointDashboardService
{
    public const DEFAULT_PRIORITY_COLOR = 'var(--bs-secondary)';

    /** @var array<string, Property|null> */
    private array $properties = [];

    /** @var array<string, ServiceCategory|null> */
    private array $serviceCategories = [];

    public function __construct(
        private readonly CriticalPointRepository $repository,
        private readonly CriticalPointAuthorizer $authorizer,
        private readonly PropertyRepository $propertyRepository,
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
    ) {}

    public function __invoke(GetCriticalPointDashboardRequest $request, User $user): GetCriticalPointDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CriticalPoint(id: CriticalPointId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $criticalPoints = $this->repository->findByFilter($request);

        $preventionPossibleCriticalPoints = 0;
        $eliminationOnlyCriticalPoints = 0;
        $coveredPropertyIds = [];

        $countByStage = [];

        foreach (SupplyChainStage::cases() as $stage) {
            $countByStage[$stage->value] = 0;
        }

        $countByPriority = [];
        $priorityColors = [];

        foreach (Priority::cases() as $priority) {
            $countByPriority[$priority->value] = 0;
            $priorityColors[] = SurplusDestination::tryFrom($priority->value)?->color() ?? self::DEFAULT_PRIORITY_COLOR;
        }

        $countByProperty = [];
        $countByServiceCategory = [];

        foreach ($criticalPoints as $criticalPoint) {
            ++$countByStage[$criticalPoint->supplyChainStage()->value];

            $anyPriorityPossible = false;

            foreach ($this->assessmentsByPriority($criticalPoint) as $priorityValue => $isPossible) {
                if ($isPossible) {
                    ++$countByPriority[$priorityValue];
                    $anyPriorityPossible = true;
                }
            }

            if ($criticalPoint->prevention()->isPossible) {
                ++$preventionPossibleCriticalPoints;
            }

            if (!$anyPriorityPossible) {
                ++$eliminationOnlyCriticalPoints;
            }

            if ($criticalPoint->propertyId() instanceof PropertyId) {
                $coveredPropertyIds[(string) $criticalPoint->propertyId()] = true;

                $propertyName = $this->propertyName($criticalPoint->propertyId());
                $countByProperty[$propertyName] = ($countByProperty[$propertyName] ?? 0) + 1;
            }

            foreach ($criticalPoint->serviceCategoryIds() as $serviceCategoryId) {
                $serviceCategoryName = $this->serviceCategoryName($serviceCategoryId);
                $countByServiceCategory[$serviceCategoryName] = ($countByServiceCategory[$serviceCategoryName] ?? 0) + 1;
            }
        }

        arsort($countByProperty);
        arsort($countByServiceCategory);

        return new GetCriticalPointDashboardResponse(
            totalCriticalPoints: count($criticalPoints),
            preventionPossibleCriticalPoints: $preventionPossibleCriticalPoints,
            eliminationOnlyCriticalPoints: $eliminationOnlyCriticalPoints,
            coveredProperties: count($coveredPropertyIds),
            bySupplyChainStage: DashboardChart::fromCounts($countByStage),
            byPriority: DashboardChart::fromCounts($countByPriority, $priorityColors),
            byProperty: DashboardChart::fromCounts($countByProperty),
            byServiceCategory: DashboardChart::fromCounts($countByServiceCategory),
        );
    }

    /**
     * @return array<string, bool> indexed by Priority value
     */
    private function assessmentsByPriority(CriticalPoint $criticalPoint): array
    {
        return [
            Priority::Prevention->value => $criticalPoint->prevention()->isPossible,
            Priority::Transformation->value => $criticalPoint->transformation()->isPossible,
            Priority::HumanDonation->value => $criticalPoint->humanDonation()->isPossible,
            Priority::AnimalFeed->value => $criticalPoint->animalFeed()->isPossible,
            Priority::Composting->value => $criticalPoint->composting()->isPossible,
        ];
    }

    private function propertyName(PropertyId $propertyId): string
    {
        $key = (string) $propertyId;

        if (!array_key_exists($key, $this->properties)) {
            $this->properties[$key] = $this->propertyRepository->find($propertyId);
        }

        $property = $this->properties[$key];

        return $property instanceof Property ? $property->name() : $key;
    }

    private function serviceCategoryName(ServiceCategoryId $serviceCategoryId): string
    {
        $key = (string) $serviceCategoryId;

        if (!array_key_exists($key, $this->serviceCategories)) {
            $this->serviceCategories[$key] = $this->serviceCategoryRepository->find($serviceCategoryId);
        }

        $serviceCategory = $this->serviceCategories[$key];

        return $serviceCategory instanceof ServiceCategory ? $serviceCategory->name() : $key;
    }
}
