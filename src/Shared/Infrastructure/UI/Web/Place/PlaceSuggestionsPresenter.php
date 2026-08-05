<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Place;

use App\Shared\Domain\Model\Geo\PlaceSuggestion;

/** Maps place suggestions to the payload the autocomplete Stimulus controller consumes. */
final class PlaceSuggestionsPresenter
{
    /**
     * @param list<PlaceSuggestion> $suggestions
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toChoices(array $suggestions): array
    {
        return array_map(
            static fn(PlaceSuggestion $suggestion): array => [
                'value' => $suggestion->reference,
                'label' => null !== $suggestion->secondaryLabel && '' !== $suggestion->secondaryLabel
                    ? $suggestion->label . ', ' . $suggestion->secondaryLabel
                    : $suggestion->label,
            ],
            $suggestions,
        );
    }
}
