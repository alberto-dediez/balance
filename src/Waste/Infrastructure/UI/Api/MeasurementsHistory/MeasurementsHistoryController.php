<?php

namespace App\Waste\Infrastructure\UI\Api\MeasurementsHistory;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\UI\Api\MeasurementsHistory\Measurement;
use App\Waste\Infrastructure\UI\Api\MeasurementsHistory\MeasurementHistoryResponse;

#[Route('/measurements/history', name: 'api_measurements_history', methods: ['GET'])]
#[OA\Get( 
    summary: "Get history of measurements",
    description: "Return last measurements registered by logged user",
    tags: ['Waste']
)]
#[OA\Response(
    response: 200,
    description: 'Returns list of measurements',
    content: new Model(type: MeasurementHistoryResponse::class)
)]
class MeasurementsHistoryController extends AbstractController
{
    public function __invoke(
        ListMeasurementsService $listMeasurements,
        PropertyExtension $propertyExtension,
        WasteExtension $wasteExtension,
        WasteManagerExtension $wasteManagerExtension,
        TranslatorInterface $translator
    ): JsonResponse
    {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $response = $listMeasurements(
            new ListMeasurementsRequest(
                userId: $user->id(),
                accountId: $user->accountId()
            ),
            $user
        );

        $username = $user->username();
        $measurements = [];

        if ($response->totalResults > 0) {
            foreach ($response->measurements as $measurement) {
                $measurements[] = new Measurement(
                    property: $propertyExtension->propertyName($measurement->propertyId()),
                    waste: $wasteExtension->wasteName($measurement->wasteId()),
                    wasteManager: $wasteManagerExtension->wasteManagerName($measurement->wasteManagerId()),
                    quantity: $measurement->quantity(),
                    load: $measurement->load() . ($measurement->measurementMethod() == MeasurementMethod::Volume ? ' %' : ' Kg'),
                    purpose: $translator->trans($measurement->purpose()->value, [], 'purpose'),
                    registeredAt: $measurement->registeredAt()->format('Y-m-d H:i:s')
                );
            }
        }

        return new JsonResponse(new MeasurementHistoryResponse($username, $measurements));
    }
}
