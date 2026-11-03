<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\Shared\Domain\Model\Locale\Locale;

trait MaterialityAnnexFiguresTrait
{
    /** @return list<list<string>> */
    private function materialityMethodologyRows(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): array
    {
        $assessment = $annex->assessment;
        $threshold = $assessment->thresholdPercent ?? $this->materialityThresholdOfTopics($annex);
        $criteria = [];

        foreach ($assessment->answers ?? [] as $answer) {
            $criteria[$answer->criterionKey] = $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value);
        }

        $version = '---';

        if (null !== $assessment?->questionSetVersion) {
            $version = $assessment->questionSetVersion->format('d/m/Y');

            if ($assessment->questionSetChangedSince) {
                $version .= ' · ' . $this->trans('el cuestionario ha cambiado desde entonces', $locale);
            }
        }

        return [
            [$this->trans('Norma', $locale), $this->trans('GHG Protocol Scope 3 Standard, capítulo 6', $locale)],
            [$this->trans('Criterios evaluados', $locale), [] === $criteria ? '---' : implode(', ', $criteria)],
            [$this->trans('Escala', $locale), $this->trans('Bajo / Medio / Alto = 1 / 2 / 3; No / Sí = 1 / 3; relevancia normalizada 0–100 %', $locale)],
            [$this->trans('Umbral de relevancia', $locale), null === $threshold ? '---' : $threshold . ' %'],
            [$this->trans('Regla de magnitud alta', $locale), null === $assessment?->highMagnitudeRule ? '---' : ($assessment->highMagnitudeRule ? $this->trans('Activada', $locale) : $this->trans('Desactivada', $locale))],
            [$this->trans('Sector', $locale), $assessment->sectorName ?? '---'],
            [$this->trans('Evaluación iniciada el', $locale), $assessment?->startedAt->format('d/m/Y') ?? '---'],
            [$this->trans('Completada el', $locale), $assessment?->completedAt?->format('d/m/Y') ?? '---'],
            [$this->trans('Completada por', $locale), $assessment->completedByName ?? '---'],
            [$this->trans('Identificador de la evaluación', $locale), $assessment->assessmentId ?? '---'],
            [$this->trans('Versión de las preguntas', $locale), $version],
            [$this->trans('Estado de la evaluación', $locale), null === $assessment ? '---' : ($assessment->completed ? $this->trans('Completada', $locale) : $this->trans('Borrador tras reapertura', $locale))],
        ];
    }

    /** @return list<list<string>> */
    private function materialityQuestionRows(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): array
    {
        $questions = [];

        foreach ($annex->assessment->answers ?? [] as $answer) {
            $questions[$answer->questionId] ??= $answer;
        }

        usort($questions, static fn(MaterialityAssessmentSnapshotAnswer $a, MaterialityAssessmentSnapshotAnswer $b): int => [$a->criterionKey, $a->text] <=> [$b->criterionKey, $b->text]);

        return array_map(fn(MaterialityAssessmentSnapshotAnswer $answer): array => [
            $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value),
            $this->materialityQuestionText($answer, $locale),
            $this->materialityAnswerScale($answer, $locale),
            (string) $answer->weight,
        ], $questions);
    }

    /** @return list<list<string>> */
    private function materialityTopicRows(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): array
    {
        return array_map(fn(MaterialityAnnexTopicLine $topic): array => $this->materialityTopicRow($topic, $locale), $annex->topics);
    }

    /** @return list<string> */
    private function materialityTopicRow(MaterialityAnnexTopicLine $topic, Locale $locale): array
    {
        return [
            null === $topic->number ? '---' : (string) $topic->number,
            $this->materialityTopicName($topic, $locale),
            null === $topic->scorePercent ? '---' : $this->number($topic->scorePercent) . ' %',
            null === $topic->thresholdPercent ? '---' : $topic->thresholdPercent . ' %',
            $this->materialityVerdictLabel($topic, $locale),
            $this->materialityStateLabel($topic, $locale),
            $this->materialityOverrideLabel($topic, $locale),
        ];
    }

    /** @return list<list<string>> */
    private function materialityAnswerRows(MaterialityAnnexJustification $justification, Locale $locale): array
    {
        $rows = array_map(fn(MaterialityAssessmentSnapshotAnswer $answer): array => [
            $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value),
            $this->materialityQuestionText($answer, $locale),
            '' === $answer->answerLabelKey ? '---' : $this->translator->trans($answer->answerLabelKey, [], 'materialityAnswerValueType', $locale->value),
            (string) $answer->weight,
            (string) $answer->points,
        ], $justification->answers);

        if (null !== $justification->points) {
            $rows[] = [
                $this->trans('Total', $locale),
                $this->trans('rango {min}–{max}', $locale, ['{min}' => (string) $justification->minPoints, '{max}' => (string) $justification->maxPoints]),
                '',
                '',
                (string) $justification->points,
            ];
        }

        return $rows;
    }

    private function materialityJustificationText(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, MaterialityAnnexJustification $justification, Locale $locale): string
    {
        $topic = $justification->topic;
        $assessment = $annex->assessment;
        $reason = null === $topic->exclusionReason ? '---' : $this->translator->trans($topic->exclusionReason->value, [], 'exclusionReason', $locale->value);
        $sentences = ['**' . $this->materialityPlain($this->materialityJustificationHeading($justification, $locale)) . '**'];

        if (true === $assessment?->completed) {
            $sentences[] = $this->trans('Evaluada con los criterios del GHG Protocol Scope 3 Standard (capítulo 6) en la evaluación de materialidad completada el {date} por {user}.', $locale, [
                '{date}' => $assessment->completedAt?->format('d/m/Y') ?? '---',
                '{user}' => $this->materialityPlain($assessment->completedByName ?? '---'),
            ]);
        } elseif (null !== $assessment) {
            $sentences[] = $this->trans('Evaluada con los criterios del GHG Protocol Scope 3 Standard (capítulo 6); la evaluación de materialidad fue reabierta y no se ha vuelto a completar, por lo que las respuestas vigentes no se muestran.', $locale);
        } else {
            $sentences[] = $this->trans('Evaluada con los criterios del GHG Protocol Scope 3 Standard (capítulo 6) en el análisis de materialidad aplicado el {date}.', $locale, [
                '{date}' => $topic->recommendedAt?->format('d/m/Y') ?? '---',
            ]);
        }

        if ([] !== $justification->answers) {
            $answers = array_map(fn(MaterialityAssessmentSnapshotAnswer $answer): string => $this->trans('{criterion}: «{question}», respuesta {answer} ({value} × {weight} = {points} puntos)', $locale, [
                '{criterion}' => $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value),
                '{question}' => $this->materialityPlain($answer->text),
                '{answer}' => '' === $answer->answerLabelKey ? '---' : $this->translator->trans($answer->answerLabelKey, [], 'materialityAnswerValueType', $locale->value),
                '{value}' => (string) ($answer->value ?? 0),
                '{weight}' => (string) $answer->weight,
                '{points}' => (string) $answer->points,
            ]), $justification->answers);
            $sentences[] = implode('; ', $answers) . '.';
        }

        if (null !== $topic->scorePercent && null !== $topic->thresholdPercent) {
            $below = $topic->scorePercent < $topic->thresholdPercent;
            $parameters = ['{score}' => $this->number($topic->scorePercent), '{threshold}' => (string) $topic->thresholdPercent];

            if (null !== $justification->points) {
                $parameters += ['{points}' => (string) $justification->points, '{min}' => (string) $justification->minPoints, '{max}' => (string) $justification->maxPoints];
                $sentences[] = $below
                    ? $this->trans('Total {points} puntos sobre un rango de {min} a {max}: relevancia {score} %, por debajo del umbral del {threshold} %.', $locale, $parameters)
                    : $this->trans('Total {points} puntos sobre un rango de {min} a {max}: relevancia {score} %, igual o superior al umbral del {threshold} %.', $locale, $parameters);
            } else {
                $sentences[] = $below
                    ? $this->trans('Relevancia {score} %, por debajo del umbral del {threshold} %.', $locale, $parameters)
                    : $this->trans('Relevancia {score} %, igual o superior al umbral del {threshold} %.', $locale, $parameters);
            }
        }

        if (null !== $topic->verdict) {
            $verdict = $this->translator->trans($topic->verdict->value, [], 'materialityVerdictType', $locale->value);
            $sentences[] = $topic->forcedByHighMagnitude
                ? $this->trans('Veredicto: {verdict} (magnitud alta).', $locale, ['{verdict}' => $verdict])
                : $this->trans('Veredicto: {verdict}.', $locale, ['{verdict}' => $verdict]);
        }

        $sentences[] = null === $topic->exclusionComment || '' === $topic->exclusionComment
            ? $this->trans('La categoría quedó excluida del inventario con motivo «{reason}».', $locale, ['{reason}' => $reason])
            : $this->trans('La categoría quedó excluida del inventario con motivo «{reason}» y comentario «{comment}».', $locale, ['{reason}' => $reason, '{comment}' => $this->materialityPlain($topic->exclusionComment)]);

        if (null !== $topic->overriddenByName) {
            $sentences[] = $this->trans('Recomendación anulada por {user} el {date}: «{justification}».', $locale, [
                '{user}' => $this->materialityPlain($topic->overriddenByName),
                '{date}' => $topic->overriddenAt?->format('d/m/Y') ?? '---',
                '{justification}' => $this->materialityPlain($topic->overrideJustification ?? ''),
            ]);
        }

        return implode(' ', $sentences);
    }

    private function materialityJustificationHeading(MaterialityAnnexJustification $justification, Locale $locale): string
    {
        $topic = $justification->topic;
        $reason = null === $topic->exclusionReason ? '---' : $this->translator->trans($topic->exclusionReason->value, [], 'exclusionReason', $locale->value);

        return $this->trans('{category} — Excluida ({reason}).', $locale, ['{category}' => $this->materialityTopicLabel($topic, $locale), '{reason}' => $reason]);
    }

    private function materialityAnnexStatement(OrganizationalCarbonFootprintReportData $data, Locale $locale): ?string
    {
        $annex = $data->materialityAnnex;

        if ($data->scope3Excluded) {
            $reason = null === $data->scope3ExclusionReason ? '---' : $this->translator->trans($data->scope3ExclusionReason->value, [], 'exclusionReason', $locale->value);

            return null === $data->scope3ExclusionComment || '' === $data->scope3ExclusionComment
                ? $this->trans('El alcance 3 se ha excluido en su totalidad: {reason}. Las recomendaciones del análisis de materialidad no se aplican.', $locale, ['{reason}' => $reason])
                : $this->trans('El alcance 3 se ha excluido en su totalidad: {reason}. {comment}. Las recomendaciones del análisis de materialidad no se aplican.', $locale, ['{reason}' => $reason, '{comment}' => $this->materialityPlain($data->scope3ExclusionComment)]);
        }

        if (null === $annex || $annex->isAnalysisMissing()) {
            return $this->trans('No se ha realizado un análisis de materialidad para esta huella. Las exclusiones del alcance 3 se sustentan únicamente en el motivo y el comentario declarados en la tabla “Emisiones incluidas en cada alcance”.', $locale);
        }

        if ($annex->isReopened()) {
            return $this->trans('La evaluación fue reabierta y no se ha vuelto a completar; las respuestas vigentes no se muestran.', $locale);
        }

        return null;
    }

    private function materialityFootnote(OrganizationalCarbonFootprintReportData $data, Locale $locale): ?string
    {
        if (!$data->hasMaterialityDecisions()) {
            return null;
        }

        return $this->trans('Las exclusiones del alcance 3 se justifican en el anexo “Análisis de materialidad del alcance 3”.', $locale);
    }

    private function materialityPlain(string $text): string
    {
        return addcslashes(htmlspecialchars(preg_replace('/\s+/u', ' ', $text) ?? $text, \ENT_QUOTES), '\\*_#[]');
    }

    private function materialityTopicLabel(MaterialityAnnexTopicLine $topic, Locale $locale): string
    {
        if (null === $topic->name && null !== $topic->source) {
            return $this->sourceLabel($topic->source, $locale);
        }

        if (null === $topic->number) {
            return $this->materialityTopicName($topic, $locale);
        }

        return $this->trans('Cat. {number}', $locale, ['{number}' => (string) $topic->number]) . ' · ' . $this->materialityTopicName($topic, $locale);
    }

    private function materialityTopicName(MaterialityAnnexTopicLine $topic, Locale $locale): string
    {
        if (null !== $topic->name) {
            return $topic->name;
        }

        return null === $topic->source ? '---' : $this->translator->trans($topic->source->value, [], 'source', $locale->value);
    }

    private function materialityVerdictLabel(MaterialityAnnexTopicLine $topic, Locale $locale): string
    {
        if (null === $topic->verdict) {
            return null !== $topic->exclusionReason ? $this->trans('Sin resultado de materialidad', $locale) : '---';
        }

        $verdict = $this->translator->trans($topic->verdict->value, [], 'materialityVerdictType', $locale->value);

        return $topic->forcedByHighMagnitude ? $verdict . ' · ' . $this->trans('Magnitud alta', $locale) : $verdict;
    }

    private function materialityStateLabel(MaterialityAnnexTopicLine $topic, Locale $locale): string
    {
        $state = $this->translator->trans($topic->state->value, [], 'materialityCategoryStateType', $locale->value);

        if (null !== $topic->verdict || null === $topic->exclusionReason) {
            return $state;
        }

        $exclusion = $state . ' · ' . $this->translator->trans($topic->exclusionReason->value, [], 'exclusionReason', $locale->value);

        return null === $topic->exclusionComment || '' === $topic->exclusionComment ? $exclusion : $exclusion . ' · ' . $topic->exclusionComment;
    }

    private function materialityOverrideLabel(MaterialityAnnexTopicLine $topic, Locale $locale): string
    {
        if (null === $topic->overriddenByName) {
            return '---';
        }

        return sprintf('%s · %s · %s', $topic->overriddenByName, $topic->overriddenAt?->format('d/m/Y') ?? '---', $topic->overrideJustification ?? '');
    }

    private function materialityQuestionText(MaterialityAssessmentSnapshotAnswer $answer, Locale $locale): string
    {
        if ($answer->questionRemoved) {
            return $answer->text . ' (' . $this->trans('retirada', $locale) . ')';
        }

        if ($answer->questionChangedSince) {
            return $answer->text . ' (' . $this->trans('modificada', $locale) . ')';
        }

        return $answer->text;
    }

    private function materialityAnswerScale(MaterialityAssessmentSnapshotAnswer $answer, Locale $locale): string
    {
        return $this->translator->trans($answer->answerScaleKey, [], 'materialityAnswerScaleType', $locale->value);
    }

    private function materialityThresholdOfTopics(OrganizationalCarbonFootprintReportMaterialityAnnex $annex): ?int
    {
        foreach ($annex->topics as $topic) {
            if (null !== $topic->thresholdPercent) {
                return $topic->thresholdPercent;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function materialityQuestionHeader(Locale $locale): array
    {
        return [$this->trans('Criterio', $locale), $this->trans('Pregunta', $locale), $this->trans('Escala', $locale), $this->trans('Peso', $locale)];
    }

    /** @return list<string> */
    private function materialityTopicHeader(Locale $locale): array
    {
        return [
            $this->trans('Cat.', $locale),
            $this->trans('Categoría', $locale),
            $this->trans('Relevancia', $locale),
            $this->trans('Umbral', $locale),
            $this->trans('Veredicto', $locale),
            $this->trans('Estado en la huella', $locale),
            $this->trans('Anulación', $locale),
        ];
    }

    /** @return list<string> */
    private function materialityAnswerHeader(Locale $locale): array
    {
        return [$this->trans('Criterio', $locale), $this->trans('Pregunta', $locale), $this->trans('Respuesta', $locale), $this->trans('Peso', $locale), $this->trans('Puntos', $locale)];
    }
}
