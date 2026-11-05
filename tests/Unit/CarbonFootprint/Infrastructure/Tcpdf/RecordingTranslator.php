<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use Symfony\Contracts\Translation\TranslatorInterface;

final class RecordingTranslator implements TranslatorInterface
{
    /** @var list<string> */
    public array $ids = [];

    public function __construct(private readonly TranslatorInterface $translator) {}

    /** @param array<string, mixed> $parameters */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->ids[] = $id;

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}
