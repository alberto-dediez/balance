<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint;

use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsRequest;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsService;
use App\CarbonFootprint\Application\Activity\WasteContainerQuantityResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/emissions/calculate', name: 'events_carbon_footprint_emissions_calculate', methods: ['POST'])]
class CalculateEmissionsController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        CalculateActivityEmissionsService $calculateActivityEmissions,
        WasteContainerQuantityResolver $wasteContainerQuantityResolver,
        LoggedUser $loggedUser,
        Request $request,
    ): JsonResponse {
        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $eventCarbonFootprint);

        $payload = $request->getPayload();

        $activityType = ActivityType::tryFrom($payload->getString('type'));
        $condition = ItemCondition::tryFrom($payload->getString('condition'));
        $unit = Unit::tryFrom($payload->getString('unit'));
        $itemId = $payload->getString('itemId');
        $secondaryItemId = $payload->getString('itemId2');
        $mode = $payload->getString('mode');
        $isContainerMode = ActivityType::Waste === $activityType && 'container' === $mode;

        $hasRequiredItem = ActivityType::Electricity === $activityType
            ? ('' !== $itemId || '' !== $secondaryItemId)
            : '' !== $itemId;

        if (null === $activityType || !$hasRequiredItem || (null === $unit && !$isContainerMode)) {
            return $this->json(['error' => 'Invalid parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            if ($isContainerMode) {
                $containerUnit = Unit::tryFrom($payload->getString('containerUnit'));
                $containerCapacity = (float) str_replace(',', '.', $payload->getString('containerCapacity'));
                $fillPercentage = (float) str_replace(',', '.', $payload->getString('fillPercentage'));

                if (null === $containerUnit || Magnitude::Volume !== $containerUnit->magnitude()) {
                    return $this->json(['error' => 'Invalid parameters'], Response::HTTP_BAD_REQUEST);
                }

                $quantity = $wasteContainerQuantityResolver->resolve(
                    new WasteId($itemId),
                    new Quantity($containerCapacity, $containerUnit),
                    $fillPercentage,
                );
            } else {
                $quantity = new Quantity((float) str_replace(',', '.', $payload->getString('quantity')), $unit);
            }

            $response = $calculateActivityEmissions(new CalculateActivityEmissionsRequest(
                eventCarbonFootprintId: $eventCarbonFootprint->id(),
                activityType: $activityType,
                itemId: $itemId,
                condition: $condition,
                quantity: $quantity,
                secondaryItemId: '' !== $secondaryItemId ? $secondaryItemId : null,
            ), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'emissions' => [
                'co2eq' => $response->emissions->co2eq(),
                'co2' => $response->emissions->co2(),
                'ch4' => $response->emissions->ch4(),
                'n2o' => $response->emissions->n2o(),
            ],
            'emissionFactor' => [
                'co2eq' => $response->emissionFactor->co2eq(),
                'unit' => $response->emissionFactor->unit()->value,
                'year' => $response->emissionFactor->year(),
            ],
        ]);
    }
}
