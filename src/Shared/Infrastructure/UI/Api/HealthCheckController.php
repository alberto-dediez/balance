<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Api;

use App\Shared\Application\Health\CheckHealthService;
use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health-check', name: 'api_health_check', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: "Check if the system is up and running. Returns status: 'ok' if every component responds, or status: 'error' with a 503 if any component fails.", summary: 'Verify system status', tags: ['Health Check'])]
#[OA\Response(
    response: 200,
    description: 'Every component of the system is healthy',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'ok'),
            new OA\Property(property: 'checks', type: 'object', example: ['database' => 'ok', 'queue' => 'ok'], additionalProperties: new OA\AdditionalProperties(type: 'string')),
        ],
    ),
)]
#[OA\Response(
    response: 503,
    description: 'Some component of the system is failing',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'error'),
            new OA\Property(property: 'checks', type: 'object', example: ['database' => 'ok', 'queue' => 'error'], additionalProperties: new OA\AdditionalProperties(type: 'string')),
        ],
    ),
)]

class HealthCheckController extends AbstractController
{
    public function __invoke(CheckHealthService $checkHealth): JsonResponse
    {
        $report = $checkHealth();

        return new JsonResponse($report->toArray(), $report->isHealthy() ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
