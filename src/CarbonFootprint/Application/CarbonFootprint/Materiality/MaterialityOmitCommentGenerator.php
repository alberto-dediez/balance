<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaterialityOmitCommentGenerator
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param list<MaterialityDecisionInput> $decisions
     *
     * @return array<string, string>
     */
    public function forDecisions(CarbonFootprint $carbonFootprint, array $decisions): array
    {
        $comments = [];

        foreach ($decisions as $decision) {
            if (!$decision->verdict->recommendsExclusion() || null === $decision->topicNumber) {
                continue;
            }

            $source = Source::ofGhgProtocolCategory($decision->topicNumber);

            if (null === $source) {
                continue;
            }

            $comments[$source->value] = $this->translator->trans(
                'Análisis de materialidad: relevancia {score} % por debajo del umbral {threshold} %',
                [
                    '{score}' => number_format($decision->scorePercent, 2, ',', '.'),
                    '{threshold}' => $decision->thresholdPercent,
                ],
                null,
                LocaleGetter::default()->value,
            );
        }

        return $comments;
    }
}
