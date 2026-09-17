<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

final class NameNormalizer
{
    public static function normalize(string $name): string
    {
        $normalized = mb_strtolower(trim($name));

        $decomposed = \Normalizer::normalize($normalized, \Normalizer::FORM_D);

        if (false !== $decomposed) {
            $normalized = $decomposed;
        }

        $normalized = preg_replace('/\p{Mn}+/u', '', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
