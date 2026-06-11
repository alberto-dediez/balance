<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Dashboard;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class GetSurplusDashboardService
{
    public const UNASSESSED = 'Unassessed';

    public const UNASSESSED_COLOR = 'var(--bs-secondary)';

    /** @var array<string, CriticalPoint|null> */
    private array $criticalPoints = [];

    /** @var array<string, Property|null> */
    private array $properties = [];

    /** @var array<string, SurplusCause|null> */
    private array $surplusCauses = [];

    /** @var array<string, ServiceCategory|null> */
    private array $serviceCategories = [];

    public function __construct(
        private readonly SurplusRepository $repository,
        private readonly SurplusAuthorizer $authorizer,
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly SurplusCauseRepository $surplusCauseRepository,
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
    ) {}

    public function __invoke(GetSurplusDashboardRequest $request, User $user): GetSurplusDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Surplus(id: SurplusId::generate(), accountId: $request->accountId ?? new AccountId('00000000-0000-0000-0000-000000000000')), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $surpluses = $this->repository->findByFilter($request);

        $displayUnit = $request->displayUnit ?? DeliveryLogUnit::Kg;
        $kgPerRation = null !== $request->kgPerRation && $request->kgPerRation > 0
            ? $request->kgPerRation
            : GetSurplusDashboardRequest::MITECO_KG_PER_RATION;

        $totalKg = 0.0;
        $totalRations = 0.0;
        $totalQuantity = 0.0;
        $recoveredSurpluses = 0;
        $eliminatedSurpluses = 0;
        $unassessedSurpluses = 0;

        $countByDestination = [];
        $destinationColors = [];

        foreach (SurplusDestination::cases() as $destination) {
            $countByDestination[$destination->value] = 0;
            $destinationColors[] = $destination->color();
        }

        $countByDestination[self::UNASSESSED] = 0;
        $destinationColors[] = self::UNASSESSED_COLOR;

        $countByFoodType = [];
        $countByServiceCategory = [];
        $countBySurplusCause = [];
        $countByCriticalPoint = [];
        $countByProperty = [];
        $quantityByMonth = [];

        foreach ($surpluses as $surplus) {
            if (DeliveryLogUnit::Kg === $surplus->unit()) {
                $totalKg += $surplus->quantity();
            } else {
                $totalRations += $surplus->quantity();
            }

            $quantity = $this->convert($surplus->quantity(), $surplus->unit(), $displayUnit, $kgPerRation);
            $totalQuantity += $quantity;

            $month = $surplus->date()->format('Y-m');
            $quantityByMonth[$month] = ($quantityByMonth[$month] ?? 0.0) + $quantity;

            $destination = $surplus->destination();

            if (!$destination instanceof SurplusDestination) {
                ++$unassessedSurpluses;
                ++$countByDestination[self::UNASSESSED];
            } else {
                ++$countByDestination[$destination->value];

                if (SurplusDestination::Elimination === $destination) {
                    ++$eliminatedSurpluses;
                } else {
                    ++$recoveredSurpluses;
                }
            }

            $foodType = $surplus->foodType()->value;
            $countByFoodType[$foodType] = ($countByFoodType[$foodType] ?? 0) + 1;

            $serviceCategoryName = $this->serviceCategoryName($surplus);
            $countByServiceCategory[$serviceCategoryName] = ($countByServiceCategory[$serviceCategoryName] ?? 0) + 1;

            $surplusCauseName = $this->surplusCauseName($surplus);
            $countBySurplusCause[$surplusCauseName] = ($countBySurplusCause[$surplusCauseName] ?? 0) + 1;

            $criticalPoint = $this->criticalPoint($surplus);
            $criticalPointName = $criticalPoint instanceof CriticalPoint ? $criticalPoint->name() : (string) $surplus->criticalPointId();
            $countByCriticalPoint[$criticalPointName] = ($countByCriticalPoint[$criticalPointName] ?? 0) + 1;

            if ($criticalPoint instanceof CriticalPoint && $criticalPoint->propertyId() instanceof PropertyId) {
                $propertyName = $this->propertyName($criticalPoint->propertyId());
                $countByProperty[$propertyName] = ($countByProperty[$propertyName] ?? 0) + 1;
            }
        }

        arsort($countByFoodType);
        arsort($countByServiceCategory);
        arsort($countBySurplusCause);
        arsort($countByCriticalPoint);
        arsort($countByProperty);

        ksort($quantityByMonth);

        return new GetSurplusDashboardResponse(
            totalSurpluses: count($surpluses),
            totalKg: round($totalKg, 2),
            totalRations: round($totalRations, 2),
            displayUnit: $displayUnit,
            totalQuantity: round($totalQuantity, 2),
            recoveredSurpluses: $recoveredSurpluses,
            eliminatedSurpluses: $eliminatedSurpluses,
            unassessedSurpluses: $unassessedSurpluses,
            byDestination: SurplusDashboardChart::fromCounts($countByDestination, $destinationColors),
            byFoodType: SurplusDashboardChart::fromCounts($countByFoodType),
            byServiceCategory: SurplusDashboardChart::fromCounts($countByServiceCategory),
            bySurplusCause: SurplusDashboardChart::fromCounts($countBySurplusCause),
            byCriticalPoint: SurplusDashboardChart::fromCounts($countByCriticalPoint),
            byProperty: SurplusDashboardChart::fromCounts($countByProperty),
            quantityByMonth: SurplusDashboardChart::fromCounts($this->fillMonthGaps($quantityByMonth)),
        );
    }

    private function convert(float $quantity, DeliveryLogUnit $from, DeliveryLogUnit $to, float $kgPerRation): float
    {
        if ($from === $to) {
            return $quantity;
        }

        return DeliveryLogUnit::Kg === $to
            ? $quantity * $kgPerRation
            : $quantity / $kgPerRation;
    }

    private function criticalPoint(Surplus $surplus): ?CriticalPoint
    {
        $key = (string) $surplus->criticalPointId();

        if (!array_key_exists($key, $this->criticalPoints)) {
            $this->criticalPoints[$key] = $this->criticalPointRepository->find($surplus->criticalPointId());
        }

        return $this->criticalPoints[$key];
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

    private function surplusCauseName(Surplus $surplus): string
    {
        $key = (string) $surplus->surplusCauseId();

        if (!array_key_exists($key, $this->surplusCauses)) {
            $this->surplusCauses[$key] = $this->surplusCauseRepository->find($surplus->surplusCauseId());
        }

        $surplusCause = $this->surplusCauses[$key];

        return $surplusCause instanceof SurplusCause ? $surplusCause->name() : $key;
    }

    private function serviceCategoryName(Surplus $surplus): string
    {
        $key = (string) $surplus->serviceCategoryId();

        if (!array_key_exists($key, $this->serviceCategories)) {
            $this->serviceCategories[$key] = $this->serviceCategoryRepository->find($surplus->serviceCategoryId());
        }

        $serviceCategory = $this->serviceCategories[$key];

        return $serviceCategory instanceof ServiceCategory ? $serviceCategory->name() : $key;
    }

    /**
     * @param array<string, float> $quantityByMonth indexed by 'Y-m'
     *
     * @return array<string, float> indexed by 'm/Y' with empty months filled
     */
    private function fillMonthGaps(array $quantityByMonth): array
    {
        if ([] === $quantityByMonth) {
            return [];
        }

        $months = array_keys($quantityByMonth);

        $current = new \DateTimeImmutable(sprintf('%s-01', $months[0]));
        $last = new \DateTimeImmutable(sprintf('%s-01', $months[count($months) - 1]));

        $result = [];

        while ($current <= $last) {
            $result[$current->format('m/Y')] = round($quantityByMonth[$current->format('Y-m')] ?? 0.0, 2);

            $current = $current->modify('+1 month');
        }

        return $result;
    }
}
