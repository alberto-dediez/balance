<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly\AnswerAssemblyRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly\AnswerAssemblyService;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivity;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteFilter;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\AggregateRootId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/step/assembly', name: 'events_carbon_footprint_assembly')]
class AssemblyController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRepository $activityRepository,
        CombustibleRepository $combustibleRepository,
        RoadTransportRepository $roadTransportRepository,
        MachineryFuelRepository $machineryFuelRepository,
        ElectricityMarketerRepository $electricityMarketerRepository,
        GdOFactorRepository $gdOFactorRepository,
        MobilityRepository $mobilityRepository,
        OvernightStayRepository $overnightStayRepository,
        WasteRepository $wasteRepository,
        AnswerAssemblyService $answerAssembly,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$eventCarbonFootprint->status()->isMaterialsAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('assembly_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerAssembly(
                    new AnswerAssemblyRequest(
                        eventCarbonFootprintId: $eventCarbonFootprint->id(),
                        fixedFacilitiesRows: $this->parseRows($request->request->all('combustibles'), ActivityType::Combustible),
                        freightTransportRows: $this->parseRows($request->request->all('roadTransports'), ActivityType::RoadTransport),
                        machineryOperationRows: array_merge(
                            $this->parseRows($request->request->all('machineryFuels'), ActivityType::MachineryFuel),
                            $this->parseRows($request->request->all('machineryElectricity'), ActivityType::Electricity, hasSecondaryItemId: true),
                        ),
                        buildingElectricityRows: $this->parseRows($request->request->all('buildingElectricity'), ActivityType::Electricity, hasSecondaryItemId: true),
                        accommodationRows: $this->parseRows($request->request->all('overnightStays'), ActivityType::OvernightStay),
                        mobilityRows: $this->parseRows($request->request->all('mobilities'), ActivityType::Mobility),
                        wasteManagementRows: $this->parseRows($request->request->all('wastes'), ActivityType::Waste),
                    ),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_celebration', ['eventId' => $eventId]);
        }

        $year = $eventCarbonFootprint->calculationYear();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        return $this->render('@carbon_footprint/event_carbon_footprints/step/assembly.html.twig', [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'eventId' => $eventId,
            'combustibles' => $this->withEmissionFactor($combustibleRepository->findByFilter(new CombustibleFilter(limit: 0)), $year, $emissionFactorGroupId),
            'roadTransports' => $this->withEmissionFactor($roadTransportRepository->findByFilter(new RoadTransportFilter(limit: 0)), $year, $emissionFactorGroupId),
            'machineryFuels' => $this->withEmissionFactor($machineryFuelRepository->findByFilter(new MachineryFuelFilter(limit: 0)), $year, $emissionFactorGroupId),
            'electricityMarketers' => $this->withEmissionFactor($electricityMarketerRepository->findByFilter(new ElectricityMarketerFilter(limit: 0)), $year, $emissionFactorGroupId),
            'gdOFactors' => $gdOFactorRepository->findByFilter(new GdOFactorFilter(limit: 0)),
            'mobilities' => $this->withEmissionFactor($mobilityRepository->findByFilter(new MobilityFilter(limit: 0)), $year, $emissionFactorGroupId),
            'overnightStays' => $this->withEmissionFactor($overnightStayRepository->findByFilter(new OvernightStayFilter(limit: 0)), $year, $emissionFactorGroupId),
            'wastes' => $this->withEmissionFactor($wasteRepository->findByFilter(new WasteFilter(limit: 0)), $year, $emissionFactorGroupId),
            'units' => Unit::cases(),
            'combustibleRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::FixedFacilities, ActivityType::Combustible),
            'roadTransportRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::FreightTransport, ActivityType::RoadTransport),
            'machineryFuelRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::MachineryOperation, ActivityType::MachineryFuel),
            'machineryElectricityRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::MachineryOperation, ActivityType::Electricity),
            'buildingElectricityRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::BuildingElectricity, ActivityType::Electricity),
            'overnightStayRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::SuppliersAndStaffAccommodation, ActivityType::OvernightStay),
            'mobilityRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::SuppliersAndStaffMobility, ActivityType::Mobility),
            'wasteRows' => $this->activityRowsFor($eventCarbonFootprint, $activityRepository, Source::WasteManagement, ActivityType::Waste),
        ]);
    }

    /**
     * @template T of Combustible|RoadTransport|MachineryFuel|ElectricityMarketer|Mobility|OvernightStay|Waste
     *
     * @param array<T> $items
     *
     * @return list<T>
     */
    private function withEmissionFactor(array $items, int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $items,
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    /**
     * @param array<array-key, mixed> $rows
     *
     * @return list<ActivityRow>
     */
    private function parseRows(array $rows, ActivityType $activityType, bool $hasSecondaryItemId = false): array
    {
        $parsed = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemId = trim((string) ($row['itemId'] ?? ''));
            $unit = Unit::tryFrom((string) ($row['unit'] ?? ''));
            $quantity = (float) str_replace(',', '.', (string) ($row['quantity'] ?? ''));
            $activityId = trim((string) ($row['activityId'] ?? ''));
            $secondaryItemId = $hasSecondaryItemId ? trim((string) ($row['itemId2'] ?? '')) : '';

            if ('' === $itemId || null === $unit || $quantity <= 0 || ($hasSecondaryItemId && '' === $secondaryItemId)) {
                continue;
            }

            $parsed[] = new ActivityRow(
                activityType: $activityType,
                itemId: $itemId,
                condition: ItemCondition::New,
                quantity: new Quantity($quantity, $unit),
                activityId: '' !== $activityId ? new ActivityId($activityId) : null,
                secondaryItemId: $hasSecondaryItemId ? $secondaryItemId : null,
            );
        }

        return $parsed;
    }

    /** @return list<array<string, mixed>> */
    private function activityRowsFor(EventCarbonFootprint $eventCarbonFootprint, ActivityRepository $activityRepository, Source $source, ActivityType $activityType): array
    {
        $rows = [];

        foreach ($eventCarbonFootprint->activities()->filterByPhase(Phase::Assembly)->filterBySource($source)->activityIds() as $activityId) {
            $activity = $activityRepository->findById($activityId);

            if (null === $activity || ActivityType::fromActivity($activity) !== $activityType) {
                continue;
            }

            $itemId = $this->itemIdOf($activity);

            if (!$itemId instanceof AggregateRootId) {
                continue;
            }

            $row = [
                'activityId' => (string) $activity->id(),
                'itemId' => (string) $itemId,
                'unit' => $activity->quantity()->unit()->value,
                'quantity' => $activity->quantity()->value(),
                'co2eq' => $activity->emissions()->co2eq(),
            ];

            $secondaryItemId = $this->secondaryItemIdOf($activity);
            if ($secondaryItemId instanceof AggregateRootId) {
                $row['itemId2'] = (string) $secondaryItemId;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function itemIdOf(Activity $activity): ?AggregateRootId
    {
        return match (true) {
            $activity instanceof CombustibleActivity   => $activity->combustibleId(),
            $activity instanceof RoadTransportActivity => $activity->roadTransportId(),
            $activity instanceof MachineryFuelActivity => $activity->machineryFuelId(),
            $activity instanceof ElectricityActivity   => $activity->electricityMarketerId(),
            $activity instanceof MobilityActivity      => $activity->mobilityId(),
            $activity instanceof OvernightStayActivity => $activity->overnightStayId(),
            $activity instanceof WasteActivity         => $activity->wasteId(),
            default => null,
        };
    }

    private function secondaryItemIdOf(Activity $activity): ?AggregateRootId
    {
        return $activity instanceof ElectricityActivity ? $activity->gdOFactorId() : null;
    }
}
