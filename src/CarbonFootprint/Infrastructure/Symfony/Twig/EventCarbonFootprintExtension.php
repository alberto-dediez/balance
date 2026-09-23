<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Twig;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EventCarbonFootprintExtension extends AbstractExtension
{
    /** @var array<string, EventCarbonFootprint|null> */
    private array $cache = [];

    /** @var array<string, CarbonFootprintReport|null> */
    private array $reportCache = [];

    public function __construct(
        private readonly EventCarbonFootprintRepository $repository,
        private readonly CarbonFootprintReportRepository $reportRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('event_carbon_footprint', $this->eventCarbonFootprint(...)),
            new TwigFunction('event_carbon_footprint_report', $this->carbonFootprintReport(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('event_carbon_footprint', $this->findEventCarbonFootprintOrCreate(...)),
        ];
    }

    public function eventCarbonFootprint(EventId|string $eventId): ?EventCarbonFootprint
    {
        $key = (string) $eventId;

        if (!array_key_exists($key, $this->cache)) {
            $id = $eventId instanceof EventId ? $eventId : new EventId($eventId);
            $this->cache[$key] = $this->repository->findByEventId($id);
        }

        return $this->cache[$key];
    }

    public function carbonFootprintReport(EventId|string $eventId): ?CarbonFootprintReport
    {
        $key = (string) $eventId;

        if (!array_key_exists($key, $this->reportCache)) {
            $carbonFootprint = $this->eventCarbonFootprint($eventId);

            $this->reportCache[$key] = null === $carbonFootprint
                ? null
                : $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Event, (string) $carbonFootprint->id());
        }

        return $this->reportCache[$key];
    }

    public function findEventCarbonFootprintOrCreate(Event $event): EventCarbonFootprint
    {
        $carbonFootprint = $this->eventCarbonFootprint($event->id());

        if (null === $carbonFootprint) {
            $carbonFootprint = new EventCarbonFootprint(
                id: EventCarbonFootprintId::generate(),
                accountId: $event->accountId(),
            );
        }

        return $carbonFootprint;
    }
}
