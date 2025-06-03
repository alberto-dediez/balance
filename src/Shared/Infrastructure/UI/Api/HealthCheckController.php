<?php

namespace App\Shared\Infrastructure\UI\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/health-check', name: 'api_health_check', methods: ['GET'])]
#[OA\Get( 
    summary: "Verify system status",
    description: "Check if the system is up and running. Returns status: 'ok' if everything is cool.",
    tags: ['Health Check']
)]
#[OA\Response(
    response: 200,
    description: 'Returns the status of the system',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'ok')
        ]
    )
)]

class HealthCheckController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
