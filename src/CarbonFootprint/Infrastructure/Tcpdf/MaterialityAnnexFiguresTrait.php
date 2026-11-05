<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\Shared\Domain\Model\Locale\Locale;

trait MaterialityAnnexFiguresTrait
{
    private const string MATRIX_INCLUDE = '#DFF2E4';
    private const string MATRIX_OMIT = '#FBE9C6';
    private const string MATRIX_NEUTRAL = '#ECEFF1';

    private function materialityMethodologyParagraph(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): string
    {
        $assessment = $annex->assessment;
        $threshold = $assessment->thresholdPercent ?? $this->materialityThresholdOfTopics($annex);
        $criteria = [];

        foreach ($assessment->answers ?? [] as $answer) {
            $criteria[$answer->criterionKey] = $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value);
        }

        $sentences = [$this->trans('El análisis sigue el GHG Protocol Scope 3 Standard (capítulo 6).', $locale)];

        if ([] !== $criteria) {
            $sentences[] = $this->trans('Criterios evaluados: {criteria}.', $locale, ['{criteria}' => $this->materialityPlain(implode(', ', $criteria))]);
        }

        $sentences[] = $this->trans('Escala: Bajo / Medio / Alto = 1 / 2 / 3; No / Sí = 1 / 3; relevancia normalizada de 0 a 100 %.', $locale);

        if (null !== $threshold) {
            $sentences[] = $this->trans('Umbral de relevancia: {threshold} %.', $locale, ['{threshold}' => (string) $threshold]);
        }

        $sentences[] = match ($assessment?->highMagnitudeRule) {
            true => $this->trans('Regla de magnitud alta: activada.', $locale),
            false => $this->trans('Regla de magnitud alta: desactivada.', $locale),
            null => $this->trans('Regla de magnitud alta: no registrada en la evaluación.', $locale),
        };

        return implode(' ', $sentences);
    }

    /** @return list<list<string>> */
    private function materialityTraceabilityRows(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): array
    {
        $assessment = $annex->assessment;
        $version = '---';

        if (null !== $assessment?->questionSetVersion) {
            $version = $assessment->questionSetVersion->format('d/m/Y');

            if ($assessment->questionSetChangedSince) {
                $version .= ' · ' . $this->trans('el cuestionario ha cambiado desde entonces', $locale);
            }
        }

        return [
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

    /** @param list<string>|null $columnWidths */
    private function buildMaterialityMatrixHtml(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale, ?array $columnWidths = null): string
    {
        $branding = $this->pdfBranding();
        $html = '<table cellpadding="4" style="width: 100%; border-collapse: collapse;"><thead><tr>';

        foreach ($this->materialityTopicHeader($locale) as $index => $headerCell) {
            $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$index]) : '';
            $html .= $this->materialityMatrixHeaderCell($headerCell, $width);
        }

        $html .= '</tr></thead><tbody>';

        foreach ($annex->topics as $rowIndex => $topic) {
            $rowColor = 0 === $rowIndex % 2 ? '#FFFFFF' : $branding->subtleHex();
            $cellColors = [4 => $this->materialityVerdictColor($topic), 5 => $this->materialityStateColor($topic) ?? $rowColor];
            $html .= '<tr nobr="true">';

            foreach ($this->materialityTopicRow($topic, $locale) as $cellIndex => $cell) {
                $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$cellIndex]) : '';
                $html .= sprintf('<td style="background-color: %s; color: %s;%s">%s</td>', $cellColors[$cellIndex] ?? $rowColor, $branding->bodyTextHex(), $width, htmlspecialchars($cell));
            }

            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function materialityVerdictColor(MaterialityAnnexTopicLine $topic): string
    {
        return match ($topic->verdict) {
            MaterialityVerdict::Calculate => self::MATRIX_INCLUDE,
            MaterialityVerdict::Omit => self::MATRIX_OMIT,
            null => self::MATRIX_NEUTRAL,
        };
    }

    private function materialityStateColor(MaterialityAnnexTopicLine $topic): ?string
    {
        return match ($topic->state) {
            MaterialityCategoryState::ActiveOverridden, MaterialityCategoryState::ExcludedOverridden, MaterialityCategoryState::PendingReview => self::MATRIX_OMIT,
            default => null,
        };
    }

    /** @return list<list<string>> */
    private function materialityAnswerRows(MaterialityAnnexJustification $justification, Locale $locale): array
    {
        $rows = array_map(fn(MaterialityAssessmentSnapshotAnswer $answer): array => [
            $this->translator->trans($answer->criterionKey, [], 'materialityCriterionType', $locale->value),
            $this->materialityQuestionText($answer, $locale),
            '' === $answer->answerLabelKey ? '---' : $this->translator->trans($answer->answerLabelKey, [], 'materialityAnswerValueType', $locale->value),
            null === $answer->value ? '---' : sprintf('%d × %d', $answer->value, $answer->weight),
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

    private function materialityJustificationText(MaterialityAnnexJustification $justification, Locale $locale): string
    {
        $topic = $justification->topic;
        $reason = null === $topic->exclusionReason ? '---' : $this->translator->trans($topic->exclusionReason->value, [], 'exclusionReason', $locale->value);
        $sentences = ['**' . $this->materialityPlain($this->materialityJustificationHeading($justification, $locale)) . '**'];

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

        if (true === $annex?->hasNoScope3Topics()) {
            return $this->trans('La evaluación de materialidad se completó sin categorías del alcance 3; las exclusiones del alcance 3 se sustentan en el motivo y el comentario declarados en la tabla “Emisiones incluidas en cada alcance”.', $locale);
        }

        if (true === $annex?->isCompletedWithoutVerdicts()) {
            return $this->trans('La evaluación de materialidad se completó antes de la activación de los veredictos automáticos; las exclusiones del alcance 3 se sustentan en el motivo y el comentario declarados en la tabla “Emisiones incluidas en cada alcance”.', $locale);
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
        return [$this->trans('Criterio', $locale), $this->trans('Pregunta', $locale), $this->trans('Respuesta', $locale), $this->trans('Valor × peso', $locale), $this->trans('Puntos', $locale)];
    }

    private function materialityNarrativeTitle(Locale $locale): string
    {
        return $this->trans('Análisis', $locale);
    }

    private function materialityEvidenceTitle(Locale $locale): string
    {
        return $this->trans('Evidencias por categoría', $locale);
    }
}
