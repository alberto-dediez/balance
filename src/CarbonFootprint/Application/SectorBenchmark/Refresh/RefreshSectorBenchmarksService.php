<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\SectorBenchmark\Refresh;

use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectResolver;
use App\CarbonFootprint\Application\SectorBenchmark\SectorBenchmarkAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkId;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkSample;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkStatistics;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RefreshSectorBenchmarksService implements CommandHandler
{
    use RegisterHandlerTrait;

    private const int YEARS_BACK = 10;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private SectorBenchmarkRepository $sectorBenchmarkRepository,
        private IntensityIndicatorsCalculator $intensityIndicatorsCalculator,
        private SectorBenchmarkAuthorizer $authorizer,
    ) {}

    public function __invoke(RefreshSectorBenchmarksRequest $request, User $user): RefreshSectorBenchmarksResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new SectorBenchmark(id: SectorBenchmarkId::generate()), Action::Register, $user);

        $currentYear = (int) $request->now->format('Y');

        /** @var array<string, array{type: CarbonFootprintReportSubjectType, segment: string, year: int, indicator: IntensityIndicator, samples: list<SectorBenchmarkSample>}> $groups */
        $groups = [];
        /** @var array<string, string> $segmentsByPropertyId */
        $segmentsByPropertyId = [];

        for ($year = $currentYear - self::YEARS_BACK; $year <= $currentYear; ++$year) {
            foreach ($this->propertyCarbonFootprintRepository->findCompletedByYear($year) as $footprint) {
                $segment = $this->segmentOf($footprint, $segmentsByPropertyId);

                if (null !== $segment) {
                    $this->collect($groups, CarbonFootprintReportSubjectType::Property, $segment, $footprint);
                }
            }

            foreach ($this->companyCarbonFootprintRepository->findCompletedByYear($year) as $footprint) {
                $this->collect($groups, CarbonFootprintReportSubjectType::Company, KpiSubject::COMPANY_SEGMENT, $footprint);
            }
        }

        $benchmarks = [];
        foreach ($groups as $group) {
            $statistics = SectorBenchmarkStatistics::from($group['samples']);

            if (null === $statistics) {
                continue;
            }

            $benchmarks[] = SectorBenchmark::compute(
                SectorBenchmarkId::generate(),
                $group['type'],
                $group['segment'],
                $group['year'],
                $group['indicator'],
                $statistics,
            );
        }

        $this->sectorBenchmarkRepository->replaceAll($benchmarks);

        return new RefreshSectorBenchmarksResponse(count($benchmarks));
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RefreshSectorBenchmarksRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RefreshSectorBenchmarksRequest;
    }

    /** @param array<string, string> $segmentsByPropertyId */
    private function segmentOf(PropertyCarbonFootprint $footprint, array &$segmentsByPropertyId): ?string
    {
        $propertyId = $footprint->propertyId();

        if (null === $propertyId) {
            return null;
        }

        $key = (string) $propertyId;

        if (!isset($segmentsByPropertyId[$key])) {
            $property = $this->propertyRepository->find($propertyId);

            if (null === $property) {
                return null;
            }

            $segmentsByPropertyId[$key] = KpiSubjectResolver::segmentKeyOf($property);
        }

        return $segmentsByPropertyId[$key];
    }

    /** @param array<string, array{type: CarbonFootprintReportSubjectType, segment: string, year: int, indicator: IntensityIndicator, samples: list<SectorBenchmarkSample>}> $groups */
    private function collect(array &$groups, CarbonFootprintReportSubjectType $type, string $segment, PropertyCarbonFootprint|CompanyCarbonFootprint $footprint): void
    {
        $accountId = $footprint->accountId();

        if (null === $accountId) {
            return;
        }

        $values = $this->intensityIndicatorsCalculator->calculate((float) $footprint->totalEmissions()->co2eq(), $footprint->intensityDenominators());

        foreach ($values as $value) {
            $key = implode('|', [$type->value, $segment, $footprint->year(), $value->indicator->value]);

            $groups[$key] ??= ['type' => $type, 'segment' => $segment, 'year' => $footprint->year(), 'indicator' => $value->indicator, 'samples' => []];
            $groups[$key]['samples'][] = new SectorBenchmarkSample((string) $accountId, $value->kgCo2eqPerUnit);
        }
    }
}
