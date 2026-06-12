<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetIncidenceDashboardService
{
    /** @var array<string, Property|null> */
    private array $properties = [];

    public function __construct(
        private readonly IncidenceRepository $repository,
        private readonly IncidenceAuthorizer $authorizer,
        private readonly PropertyRepository $propertyRepository,
        private readonly SurplusRepository $surplusRepository,
    ) {}

    public function __invoke(GetIncidenceDashboardRequest $request, User $user): GetIncidenceDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Incidence(id: IncidenceId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $incidences = $this->repository->findByFilter($request);

        $totalIncidences = 0;
        $withEvidencesIncidences = 0;
        $affectedPropertyIds = [];

        $countByType = [];

        foreach (IncidenceType::cases() as $incidenceType) {
            $countByType[$incidenceType->value] = 0;
        }

        $countByProperty = [];
        $countByMonth = [];
        $incidenceTypeMap = [];

        foreach ($incidences as $incidence) {
            if (!$this->isInRange($incidence->exceptionOccurredOn(), $request)) {
                continue;
            }

            ++$totalIncidences;
            ++$countByType[$incidence->incidenceType()->value];

            if ($incidence->evidences()->count() > 0) {
                ++$withEvidencesIncidences;
            }

            $month = $incidence->exceptionOccurredOn()->format('Y-m');
            $countByMonth[$month] = ($countByMonth[$month] ?? 0) + 1;

            if ($incidence->propertyId() instanceof PropertyId) {
                $affectedPropertyIds[(string) $incidence->propertyId()] = true;

                $propertyName = $this->propertyName($incidence->propertyId());
                $countByProperty[$propertyName] = ($countByProperty[$propertyName] ?? 0) + 1;
            }

            $incidenceTypeMap[(string) $incidence->id()] = $incidence->name();
        }

        arsort($countByProperty);
        ksort($countByMonth);

        $countSurplusByIncidence = [];

        if ([] !== $incidenceTypeMap) {
            $surpluses = $this->surplusRepository->findByFilter(new SurplusFilter(accountId: $request->accountId, limit: 0));

            foreach ($surpluses as $surplus) {
                $surplusIncidenceId = $surplus->incidenceId();

                if ($surplusIncidenceId instanceof IncidenceId) {
                    $incidenceKey = (string) $surplusIncidenceId;

                    if (isset($incidenceTypeMap[$incidenceKey])) {
                        $incidenceName = $incidenceTypeMap[$incidenceKey];
                        $countSurplusByIncidence[$incidenceName] = ($countSurplusByIncidence[$incidenceName] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($countSurplusByIncidence);

        return new GetIncidenceDashboardResponse(
            totalIncidences: $totalIncidences,
            mostFrequentIncidenceType: $this->mostFrequentIncidenceType($countByType, $totalIncidences),
            withEvidencesIncidences: $withEvidencesIncidences,
            affectedProperties: count($affectedPropertyIds),
            byType: DashboardChart::fromCounts($countByType),
            byProperty: DashboardChart::fromCounts($countByProperty),
            incidencesByMonth: DashboardChart::fromCounts($this->fillMonthGaps($countByMonth)),
            surplusByIncidence: DashboardChart::fromCounts($countSurplusByIncidence),
        );
    }

    /**
     * @param array<string, int> $countByType indexed by IncidenceType value
     */
    private function mostFrequentIncidenceType(array $countByType, int $totalIncidences): ?string
    {
        if (0 === $totalIncidences) {
            return null;
        }

        arsort($countByType);

        return array_key_first($countByType);
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

    private function isInRange(\DateTimeImmutable $date, GetIncidenceDashboardRequest $request): bool
    {
        if ($request->dateFrom instanceof \DateTimeImmutable && $date < $request->dateFrom) {
            return false;
        }

        return !($request->dateTo instanceof \DateTimeImmutable && $date > $request->dateTo);
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
