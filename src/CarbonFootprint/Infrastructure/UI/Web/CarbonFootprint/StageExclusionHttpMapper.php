<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use Symfony\Component\HttpFoundation\Request;

final class StageExclusionHttpMapper
{
    /** @return list<ExclusionAnswer> */
    public function map(Request $request, CalculationStage $stage): array
    {
        $stageSources = [];
        foreach ($stage->sections() as $section) {
            $stageSources[$section->source->value] = $section->source;
        }

        if ($stage instanceof Scope) {
            foreach ($stage->derivedSources() as $source) {
                $stageSources[$source->value] = $source;
            }
        }

        $exclusions = [];

        foreach ($request->request->all('exclusions') as $sourceValue => $row) {
            if (!is_array($row) || !isset($row['excluded'])) {
                continue;
            }

            $source = $stageSources[$sourceValue] ?? null;
            if (!$source instanceof Source) {
                throw new \InvalidArgumentException(sprintf('Source "%s" does not belong to stage "%s".', $sourceValue, $stage->key()));
            }

            $reason = ExclusionReason::tryFrom(is_string($row['reason'] ?? null) ? $row['reason'] : '')
                ?? throw new \InvalidArgumentException(sprintf('Invalid exclusion reason for source "%s".', $sourceValue));

            $comment = is_string($row['comment'] ?? null) ? trim($row['comment']) : '';

            $exclusions[] = new ExclusionAnswer($source, $reason, '' !== $comment ? $comment : null);
        }

        return $exclusions;
    }

    public function mapStage(Request $request, CalculationStage $stage): ?StageExclusionAnswer
    {
        $row = $request->request->all('stageExclusion');

        if (!isset($row['excluded'])) {
            return null;
        }

        $reason = ExclusionReason::tryFrom(is_string($row['reason'] ?? null) ? $row['reason'] : '')
            ?? throw new \InvalidArgumentException(sprintf('Invalid exclusion reason for stage "%s".', $stage->key()));

        $comment = is_string($row['comment'] ?? null) ? trim($row['comment']) : '';

        return new StageExclusionAnswer($reason, '' !== $comment ? $comment : null);
    }
}
