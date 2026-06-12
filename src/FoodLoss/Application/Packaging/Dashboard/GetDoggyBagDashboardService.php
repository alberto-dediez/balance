<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetDoggyBagDashboardService
{
    /** @var array<string, Property|null> */
    private array $properties = [];

    /** @var array<string, CriticalPoint|null> */
    private array $criticalPoints = [];

    public function __construct(
        private readonly PackagingRepository $repository,
        private readonly PackagingAuthorizer $authorizer,
        private readonly DoggyBagSignRepository $doggyBagSignRepository,
        private readonly DoggyBagExceptionRepository $doggyBagExceptionRepository,
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function __invoke(GetDoggyBagDashboardRequest $request, User $user): GetDoggyBagDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Packaging(id: PackagingId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $packagings = $this->repository->findByFilter($request);

        $totalStock = 0;

        $stockByType = [];

        foreach (PackagingType::cases() as $packagingType) {
            $stockByType[$packagingType->value] = 0;
        }

        $stockByProperty = [];

        foreach ($packagings as $packaging) {
            foreach ($packaging->stocks() as $stock) {
                if ($request->propertyId instanceof PropertyId && !$request->propertyId->equals($stock->propertyId())) {
                    continue;
                }

                $totalStock += $stock->stock();
                $stockByType[$packaging->packagingType()->value] += $stock->stock();

                $propertyName = $this->propertyName($stock->propertyId());
                $stockByProperty[$propertyName] = ($stockByProperty[$propertyName] ?? 0) + $stock->stock();
            }
        }

        arsort($stockByProperty);

        $response = new GetDoggyBagDashboardResponse(
            totalPackagings: count($packagings),
            totalStock: $totalStock,
            stockByType: DashboardChart::fromCounts($stockByType),
            stockByProperty: DashboardChart::fromCounts($stockByProperty),
        );

        $this->fillDoggyBagSigns($response, $request);
        $this->fillDoggyBagExceptions($response, $request);

        return $response;
    }

    private function fillDoggyBagSigns(GetDoggyBagDashboardResponse $response, GetDoggyBagDashboardRequest $request): void
    {
        $doggyBagSigns = $this->doggyBagSignRepository->findByFilter(new DoggyBagSignFilter(
            accountId: $request->accountId,
            propertyId: $request->propertyId,
            limit: 0,
        ));

        $countByProperty = [];

        foreach ($doggyBagSigns as $doggyBagSign) {
            if (!$doggyBagSign->propertyId() instanceof PropertyId) {
                continue;
            }

            $propertyName = $this->propertyName($doggyBagSign->propertyId());
            $countByProperty[$propertyName] = ($countByProperty[$propertyName] ?? 0) + 1;
        }

        arsort($countByProperty);

        $response->totalDoggyBagSigns = count($doggyBagSigns);
        $response->signsByProperty = DashboardChart::fromCounts($countByProperty);
    }

    private function fillDoggyBagExceptions(GetDoggyBagDashboardResponse $response, GetDoggyBagDashboardRequest $request): void
    {
        $doggyBagExceptions = $this->doggyBagExceptionRepository->findByFilter(new DoggyBagExceptionFilter(
            accountId: $request->accountId,
            propertyId: $request->propertyId,
            limit: 0,
        ));

        $countByCriticalPoint = [];
        $countByMonth = [];

        foreach ($doggyBagExceptions as $doggyBagException) {
            $month = $doggyBagException->registeredAt()->format('Y-m');
            $countByMonth[$month] = ($countByMonth[$month] ?? 0) + 1;

            if (!$doggyBagException->criticalPointId() instanceof CriticalPointId) {
                continue;
            }

            $criticalPointName = $this->criticalPointName($doggyBagException->criticalPointId());
            $countByCriticalPoint[$criticalPointName] = ($countByCriticalPoint[$criticalPointName] ?? 0) + 1;
        }

        arsort($countByCriticalPoint);
        ksort($countByMonth);

        $response->totalDoggyBagExceptions = count($doggyBagExceptions);
        $response->exceptionsByCriticalPoint = DashboardChart::fromCounts($countByCriticalPoint);
        $response->exceptionsByMonth = DashboardChart::fromCounts($this->fillMonthGaps($countByMonth));
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

    private function criticalPointName(CriticalPointId $criticalPointId): string
    {
        $key = (string) $criticalPointId;

        if (!array_key_exists($key, $this->criticalPoints)) {
            $this->criticalPoints[$key] = $this->criticalPointRepository->find($criticalPointId);
        }

        $criticalPoint = $this->criticalPoints[$key];

        return $criticalPoint instanceof CriticalPoint ? $criticalPoint->name() : $key;
    }

    /**
     * @param array<string, int> $countByMonth indexed by 'Y-m'
     *
     * @return array<string, int> indexed by 'm/Y' with empty months filled
     */
    private function fillMonthGaps(array $countByMonth): array
    {
        if ([] === $countByMonth) {
            return [];
        }

        $months = array_keys($countByMonth);

        $current = new \DateTimeImmutable(sprintf('%s-01', $months[0]));
        $last = new \DateTimeImmutable(sprintf('%s-01', $months[count($months) - 1]));

        $result = [];

        while ($current <= $last) {
            $result[$current->format('m/Y')] = $countByMonth[$current->format('Y-m')] ?? 0;

            $current = $current->modify('+1 month');
        }

        return $result;
    }
}
