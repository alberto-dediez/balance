<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestStackLocaleSetter
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $defaultLocale,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $defaultLocale = Locale::tryFrom($this->defaultLocale);
        LocaleGetter::setDefault($defaultLocale);

        $locale = Locale::tryFrom($this->requestStack->getCurrentRequest()?->getLocale());

        if (!$locale instanceof Locale) {
            return;
        }

        LocaleGetter::setLocale($locale);
    }
}
