<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationAction;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationPlan;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionTranslation;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use App\Shared\Domain\Model\Locale\Locale;

final class MitigationPlanReportBuilder
{
    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationEmissionSourcesBuilder $emissionSourcesBuilder,
    ) {}

    public function build(CarbonFootprintReportSubjectType $subjectType, CarbonFootprint $carbonFootprint, ?Locale $locale): ?OrganizationalCarbonFootprintReportMitigationPlan
    {
        $actions = array_values($this->mitigationActionRepository->findByFilter(new MitigationActionFilter(
            subjectType: $subjectType,
            carbonFootprintId: (string) $carbonFootprint->id(),
            limit: 0,
        )));

        if ([] === $actions) {
            return null;
        }

        $emissionSources = $this->emissionSourcesBuilder->build($carbonFootprint);
        $projection = (new MitigationProjector())->project($carbonFootprint->calculationYear(), $emissionSources, $actions);

        if (null === $projection) {
            return null;
        }

        $sourcesByKey = [];
        foreach ($emissionSources as $emissionSource) {
            $sourcesByKey[$emissionSource->key()] = $emissionSource;
        }

        return new OrganizationalCarbonFootprintReportMitigationPlan(
            actions: array_map(fn(MitigationAction $action): OrganizationalCarbonFootprintReportMitigationAction => $this->action($action, $locale, $sourcesByKey), $actions),
            projection: $projection,
        );
    }

    /** @param array<string, MitigationEmissionSource> $sourcesByKey */
    private function action(MitigationAction $action, ?Locale $locale, array $sourcesByKey): OrganizationalCarbonFootprintReportMitigationAction
    {
        $translation = null !== $locale ? $action->translations()->getTranslationFor($locale) : null;

        $effects = [];
        foreach ($action->effects() as $effect) {
            $emissionSource = $sourcesByKey[$effect->source()->value . '|' . $effect->itemId()] ?? null;

            $effects[] = [
                'scope' => $effect->scope(),
                'source' => $effect->source(),
                'itemName' => null !== $emissionSource ? $emissionSource->itemName : $effect->itemName(),
                'reduction' => $effect->reduction(),
            ];
        }

        return new OrganizationalCarbonFootprintReportMitigationAction(
            name: $translation instanceof MitigationActionTranslation ? $translation->name() : $action->defaultName(),
            description: $translation instanceof MitigationActionTranslation ? $translation->description() : $action->defaultDescription(),
            baseYear: $action->baseYear(),
            targetYear: $action->targetYear(),
            effects: $effects,
        );
    }
}
