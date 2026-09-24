<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Timezone;

use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;
use Twig\Extension\CoreExtension;

#[AsEventListener]
class RequestTimezoneSetter
{
    public function __construct(
        private readonly CurrentTimezoneProvider $timezoneProvider,
        private readonly Environment $twig,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->twig->getExtension(CoreExtension::class)->setTimezone($this->timezoneProvider->currentTimezone());
    }
}
