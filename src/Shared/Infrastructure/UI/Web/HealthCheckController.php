<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web;

use App\Shared\Application\Health\CheckHealthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health-check', name: 'app_health_check')]
class HealthCheckController extends AbstractController
{
    public function __invoke(CheckHealthService $checkHealth): Response
    {
        $report = $checkHealth();

        return $this->render(
            '@shared/health-check.html.twig',
            ['report' => $report],
            new Response(status: $report->isHealthy() ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE),
        );
    }
}
