<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsService;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
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

#[Route('/events/{eventId}/carbon-footprint/step/materials', name: 'events_carbon_footprint_materials')]
class MaterialsController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRepository $activityRepository,
        MaterialRepository $materialRepository,
        CateringItemRepository $cateringItemRepository,
        MerchandisingItemRepository $merchandisingItemRepository,
        AnswerMaterialsService $answerMaterials,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('materials_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $rows = array_merge(
                $this->parseRows($request->request->all('materials'), ActivityType::Material),
                $this->parseRows($request->request->all('cateringItems'), ActivityType::CateringItem),
                $this->parseRows($request->request->all('merchandisingItems'), ActivityType::MerchandisingItem),
            );

            try {
                $answerMaterials(
                    new AnswerMaterialsRequest($eventCarbonFootprint->id(), $rows),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
        }

        $year = $eventCarbonFootprint->calculationYear();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        return $this->render('@carbon_footprint/event_carbon_footprints/step/materials.html.twig', [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'eventId' => $eventId,
            'activityRows' => $this->activityRowsByType($eventCarbonFootprint, $activityRepository),
            'materials' => $this->withEmissionFactor($materialRepository->findByFilter(new MaterialFilter(limit: 0)), $year, $emissionFactorGroupId),
            'cateringItems' => $this->withEmissionFactor($cateringItemRepository->findByFilter(new CateringItemFilter(limit: 0)), $year, $emissionFactorGroupId),
            'merchandisingItems' => $this->withEmissionFactor($merchandisingItemRepository->findByFilter(new MerchandisingItemFilter(limit: 0)), $year, $emissionFactorGroupId),
            'units' => Unit::cases(),
            'conditions' => ItemCondition::cases(),
        ]);
    }

    /**
     * @template T of Material|CateringItem|MerchandisingItem
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
    private function parseRows(array $rows, ActivityType $activityType): array
    {
        $parsed = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemId = trim((string) ($row['itemId'] ?? ''));
            $condition = ItemCondition::tryFrom((string) ($row['condition'] ?? ''));
            $unit = Unit::tryFrom((string) ($row['unit'] ?? ''));
            $quantity = (float) str_replace(',', '.', (string) ($row['quantity'] ?? ''));
            $activityId = trim((string) ($row['activityId'] ?? ''));

            if ('' === $itemId || null === $condition || null === $unit || $quantity <= 0) {
                continue;
            }

            $parsed[] = new ActivityRow(
                activityType: $activityType,
                itemId: $itemId,
                condition: $condition,
                quantity: new Quantity($quantity, $unit),
                activityId: '' !== $activityId ? new ActivityId($activityId) : null,
            );
        }

        return $parsed;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function activityRowsByType(EventCarbonFootprint $eventCarbonFootprint, ActivityRepository $activityRepository): array
    {
        $rows = [
            ActivityType::Material->value => [],
            ActivityType::CateringItem->value => [],
            ActivityType::MerchandisingItem->value => [],
        ];

        foreach ($eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds() as $activityId) {
            $activity = $activityRepository->findById($activityId);

            if (null === $activity) {
                continue;
            }

            $itemId = $this->itemIdOf($activity);

            if (!$itemId instanceof AggregateRootId) {
                continue;
            }

            $rows[ActivityType::fromActivity($activity)->value][] = [
                'activityId' => (string) $activity->id(),
                'itemId' => (string) $itemId,
                'condition' => $activity instanceof MaterialActivity || $activity instanceof CateringItemActivity || $activity instanceof MerchandisingItemActivity
                    ? $activity->condition()->value
                    : ItemCondition::New->value,
                'unit' => $activity->quantity()->unit()->value,
                'quantity' => $activity->quantity()->value(),
                'co2eq' => $activity->emissions()->co2eq(),
            ];
        }

        return $rows;
    }

    private function itemIdOf(Activity $activity): ?AggregateRootId
    {
        return match (true) {
            $activity instanceof MaterialActivity          => $activity->materialId(),
            $activity instanceof CateringItemActivity      => $activity->cateringItemId(),
            $activity instanceof MerchandisingItemActivity => $activity->merchandisingItemId(),
            default => null,
        };
    }
}
