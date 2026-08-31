<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

#[AsEventListener(priority: 1000)]
class MaintenanceModeListener
{
    private const string TEMPLATE = 'bundles/TwigBundle/Exception/error503.html.twig';

    /** @var string[] */
    private const array ALLOWED_PATHS = [
        '/health-check',
        '/api/health-check',
        '/_wdt',
        '/_profiler',
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly bool $maintenanceMode,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->maintenanceMode || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_contains($path, $allowed)) {
                return;
            }
        }

        $event->setResponse(
            str_starts_with($path, '/api')
                ? new JsonResponse(['status' => 'maintenance'], Response::HTTP_SERVICE_UNAVAILABLE)
                : new Response($this->twig->render(self::TEMPLATE), Response::HTTP_SERVICE_UNAVAILABLE),
        );
    }
}
