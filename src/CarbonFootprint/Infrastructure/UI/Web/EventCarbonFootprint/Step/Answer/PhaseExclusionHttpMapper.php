<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use Symfony\Component\HttpFoundation\Request;

final class PhaseExclusionHttpMapper
{
    /** @return list<ExclusionAnswer> */
    public function map(Request $request, Phase $phase): array
    {
        $phaseSources = [];
        foreach ($phase->sections() as $section) {
            $phaseSources[$section->source->value] = $section->source;
        }

        $exclusions = [];

        foreach ($request->request->all('exclusions') as $sourceValue => $row) {
            if (!is_array($row) || !isset($row['excluded'])) {
                continue;
            }

            $source = $phaseSources[$sourceValue] ?? null;
            if (!$source instanceof Source) {
                throw new \InvalidArgumentException(sprintf('Source "%s" does not belong to phase "%s".', $sourceValue, $phase->value));
            }

            $reason = ExclusionReason::tryFrom(is_string($row['reason'] ?? null) ? $row['reason'] : '')
                ?? throw new \InvalidArgumentException(sprintf('Invalid exclusion reason for source "%s".', $sourceValue));

            $comment = is_string($row['comment'] ?? null) ? trim($row['comment']) : '';

            $exclusions[] = new ExclusionAnswer($source, $reason, '' !== $comment ? $comment : null);
        }

        return $exclusions;
    }
}
