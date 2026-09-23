<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Twig;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CarbonFootprintReportExtension extends AbstractExtension
{
    /** @var array<string, CarbonFootprintReport|null> */
    private array $cache = [];

    public function __construct(
        private readonly CarbonFootprintReportRepository $reportRepository,
        private readonly CarbonFootprintReportSubjects $subjects,
        private readonly EventRepository $eventRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('carbon_footprint_report', $this->carbonFootprintReport(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('carbon_footprint_report_subject_name', $this->subjectName(...)),
        ];
    }

    public function carbonFootprintReport(string $subjectType, string $carbonFootprintId): ?CarbonFootprintReport
    {
        $key = $subjectType . ':' . $carbonFootprintId;

        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::from($subjectType), $carbonFootprintId);
        }

        return $this->cache[$key];
    }

    public function subjectName(CarbonFootprintReport $report): string
    {
        $carbonFootprint = $this->subjects->find($report->subjectType(), $report->carbonFootprintId());

        $name = match (true) {
            $carbonFootprint instanceof EventCarbonFootprint => null !== $carbonFootprint->eventId() ? $this->eventRepository->find($carbonFootprint->eventId())?->name() : null,
            $carbonFootprint instanceof PropertyCarbonFootprint => null !== $carbonFootprint->propertyId() ? $this->propertyRepository->find($carbonFootprint->propertyId())?->name() : null,
            $carbonFootprint instanceof CompanyCarbonFootprint => null !== $carbonFootprint->companyId() ? $this->companyRepository->find($carbonFootprint->companyId())?->name() : null,
            default => null,
        };

        return $name ?? '---';
    }
}
