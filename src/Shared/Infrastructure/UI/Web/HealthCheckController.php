<?php

namespace App\Shared\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/health-check', name: 'app_health_check')]
class HealthCheckController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@shared/health-check.html.twig');
    }
}
