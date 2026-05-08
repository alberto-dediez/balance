<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanDraftTranslator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSection;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSectionTranslation;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Infrastructure\AI\Gemini\TranslateDraftPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;

class GeminiPreventionPlanDraftTranslator implements PreventionPlanDraftTranslator
{
    public function __construct(private readonly GeminiClient $geminiClient) {}

    public function translate(PreventionPlanVersion $version, Locale $targetLocale): void
    {
        $pending = $this->collectPendingSections($version, $targetLocale);

        if ([] === $pending) {
            return;
        }

        $sourceLocale = $this->resolveSourceLocale($pending);
        $sectionsPayload = $this->buildSectionsPayload($pending, $sourceLocale);

        if ($sourceLocale === $targetLocale) {
            foreach ($pending as $index => $section) {
                $section->translate($sectionsPayload[$index]['title'], $sectionsPayload[$index]['content'], $targetLocale);
            }

            return;
        }

        $response = $this->geminiClient->send(new TranslateDraftPrompt(
            sections: $sectionsPayload,
            sourceLocale: $sourceLocale,
            targetLocale: $targetLocale,
        ));

        foreach ($response as $item) {
            if (!isset($item['index'], $item['title'], $item['content'])) {
                continue;
            }

            $index = (int) $item['index'];
            $section = $pending[$index] ?? null;

            if (null === $section) {
                continue;
            }

            $section->translate(
                is_string($item['title']) ? $item['title'] : $sectionsPayload[$index]['title'],
                is_string($item['content']) ? $item['content'] : $sectionsPayload[$index]['content'],
                $targetLocale,
            );
        }
    }

    /**
     * @return array<int, PreventionPlanSection>
     */
    private function collectPendingSections(PreventionPlanVersion $version, Locale $targetLocale): array
    {
        $pending = [];

        foreach ($version->sections()->toArray() as $index => $section) {
            if (null === $section->translations()->getTranslationFor($targetLocale)) {
                $pending[$index] = $section;
            }
        }

        return $pending;
    }

    /**
     * @param array<int, PreventionPlanSection> $pending
     */
    private function resolveSourceLocale(array $pending): Locale
    {
        foreach ($pending as $section) {
            if (null !== $section->translations()->getTranslationFor(Locale::Spanish)) {
                return Locale::Spanish;
            }
        }

        foreach ($pending as $section) {
            if (null !== $section->translations()->getTranslationFor(Locale::English)) {
                return Locale::English;
            }
        }

        foreach ($pending as $section) {
            $first = $section->translations()->first();
            if ($first instanceof PreventionPlanSectionTranslation) {
                return $first->locale();
            }
        }

        return Locale::Spanish;
    }

    /**
     * @param array<int, PreventionPlanSection> $pending
     *
     * @return array<int, array{title: string, content: string}>
     */
    private function buildSectionsPayload(array $pending, Locale $sourceLocale): array
    {
        $payload = [];

        foreach ($pending as $index => $section) {
            $translation = $section->translations()->getTranslationFor($sourceLocale);

            if ($translation instanceof PreventionPlanSectionTranslation) {
                $payload[$index] = ['title' => $translation->title(), 'content' => $translation->content()];
            } else {
                $payload[$index] = ['title' => $section->title(), 'content' => $section->content()];
            }
        }

        return $payload;
    }
}
