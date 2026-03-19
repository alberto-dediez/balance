<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\MeasurementHistory;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponseGenerator;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measurements/history', name: 'api_measurements_history', methods: ['GET'])]
#[Areas(['mobile', 'admin'])]
#[OA\Get(description: 'Return last measurements registered by logged user', summary: 'Get history of measurements', tags: ['Waste'])]
#[OA\Response(
    response: 200,
    description: 'Returns list of measurements',
    content: new Model(type: MeasurementHistoryResponse::class),
)]
class MeasurementHistoryController extends AbstractController
{
    public function __invoke(
        ListMeasurementsService $listMeasurements,
        MeasurementResponseGenerator $measurementGenerator,
    ): JsonResponse {
        /**
         * @var SecurityUser $user
         */
        $user = $this->getUser();

        $response = $listMeasurements(
            new ListMeasurementsRequest(
                accountId: $user->accountId(),
                userId: $user->id(),
                limit: 10,
                orderBy: 'registeredAt',
                orderWay: 'DESC',
            ),
            $user,
        );

        $username = $user->username();
        $measurements = [];

        if ($response->totalResults > 0) {
            foreach ($response->measurements as $measurement) {
                $measurements[] = $measurementGenerator->generate($measurement);
            }
        }

        return $this->json(
            new MeasurementHistoryResponse($username, $measurements),
        );
    }
}
