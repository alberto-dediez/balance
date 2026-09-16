<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final class StepExclusionsView
{
    /**
     * @param array<string, SourceExclusion> $exclusions
     *
     * @return array<string, array{reason: string, comment: ?string}>
     */
    public static function build(array $exclusions): array
    {
        $built = [];

        foreach ($exclusions as $sourceValue => $exclusion) {
            $built[$sourceValue] = [
                'reason' => $exclusion->reason()->value,
                'comment' => $exclusion->comment(),
            ];
        }

        return $built;
    }
}
