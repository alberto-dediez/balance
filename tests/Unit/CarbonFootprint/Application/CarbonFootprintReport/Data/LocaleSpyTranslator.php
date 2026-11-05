<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;

final class LocaleSpyTranslator extends FakeTranslator
{
    /** @var array<string, ?string> */
    private array $locales = [];

    /** @param array<string, string> $parameters */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->locales[$id] = $locale;

        return parent::trans($id, $parameters, $domain, $locale);
    }

    public function localeOf(string $id): ?string
    {
        return $this->locales[$id] ?? null;
    }
}
