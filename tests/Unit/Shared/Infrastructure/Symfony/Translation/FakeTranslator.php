<?php

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

class FakeTranslator implements TranslatorInterface
{
    private $locale;

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return str_replace(array_keys($parameters), array_values($parameters), $id);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}