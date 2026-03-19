<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

use function PHPUnit\Framework\assertNotEmpty;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestStackLocaleGetter implements LocaleGetter
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $defaultLocale,
    ) {}

    public function locale(): ?Locale
    {
        $localeString = $this->requestStack->getCurrentRequest()?->getLocale();

        if (null === $localeString) {
            return $this->default();
        }

        return Locale::tryFrom($localeString);
    }

    public function default(): Locale
    {
        assertNotEmpty(Locale::tryFrom($this->defaultLocale));

        return Locale::tryFrom($this->defaultLocale);
    }
}
