<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Api;

use Nelmio\ApiDocBundle\Attribute\Areas;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health-check', name: 'api_health_check', methods: ['GET'])]
#[Areas(['admin'])]
#[OA\Get(description: "Check if the system is up and running. Returns status: 'ok' if everything is cool.", summary: 'Verify system status', tags: ['Health Check'])]
#[OA\Response(
    response: 200,
    description: 'Returns the status of the system',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'ok'),
        ],
    ),
)]

class HealthCheckController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
