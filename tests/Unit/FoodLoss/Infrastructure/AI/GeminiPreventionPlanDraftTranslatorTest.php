<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSectionTranslation;
use App\FoodLoss\Infrastructure\AI\Gemini\TranslateDraftPrompt;
use App\FoodLoss\Infrastructure\AI\GeminiPreventionPlanDraftTranslator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use PHPUnit\Framework\TestCase;

class GeminiPreventionPlanDraftTranslatorTest extends TestCase
{
    private InMemoryGeminiClient $geminiClient;
    private GeminiPreventionPlanDraftTranslator $sut;

    protected function setUp(): void
    {
        $this->geminiClient = new InMemoryGeminiClient();
        $this->sut = new GeminiPreventionPlanDraftTranslator($this->geminiClient);
    }

    public function testDoesNothingWhenAllSectionsAlreadyTranslated(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();

        $this->sut->translate($version, Locale::Spanish);

        $this->assertNull($this->geminiClient->lastPrompt());
    }

    public function testSendsAllPendingSectionsInSingleGeminiCall(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();

        $this->geminiClient->setResponse([
            ['index' => 0, 'title' => 'Introduction', 'content' => 'Introduction content.'],
            ['index' => 1, 'title' => 'Diagnosis', 'content' => 'Diagnosis content.'],
        ]);

        $this->sut->translate($version, Locale::English);

        $this->assertInstanceOf(TranslateDraftPrompt::class, $this->geminiClient->lastPrompt());
    }

    public function testAppliesTranslationsToSections(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        $this->geminiClient->setResponse([
            ['index' => 0, 'title' => 'Introduction', 'content' => 'Introduction content.'],
            ['index' => 1, 'title' => 'Diagnosis', 'content' => 'Diagnosis content.'],
        ]);

        $this->sut->translate($version, Locale::English);

        $translation0 = $sections[0]->translations()->getTranslationFor(Locale::English);
        $translation1 = $sections[1]->translations()->getTranslationFor(Locale::English);

        $this->assertInstanceOf(PreventionPlanSectionTranslation::class, $translation0);
        $this->assertSame('Introduction', $translation0->title());
        $this->assertSame('Introduction content.', $translation0->content());

        $this->assertInstanceOf(PreventionPlanSectionTranslation::class, $translation1);
        $this->assertSame('Diagnosis', $translation1->title());
        $this->assertSame('Diagnosis content.', $translation1->content());
    }

    public function testSkipsSectionsWithUnknownIndexInResponse(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        $this->geminiClient->setResponse([
            ['index' => 99, 'title' => 'Ghost', 'content' => 'Ghost content.'],
        ]);

        $this->sut->translate($version, Locale::English);

        $this->assertNull($sections[0]->translations()->getTranslationFor(Locale::English));
        $this->assertNull($sections[1]->translations()->getTranslationFor(Locale::English));
    }

    public function testAppliesPartialResponseWhenGeminiOmitsSomeSections(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        $this->geminiClient->setResponse([
            ['index' => 0, 'title' => 'Introduction', 'content' => 'Introduction content.'],
        ]);

        $this->sut->translate($version, Locale::French);

        $this->assertInstanceOf(
            PreventionPlanSectionTranslation::class,
            $sections[0]->translations()->getTranslationFor(Locale::French),
        );
        $this->assertNull($sections[1]->translations()->getTranslationFor(Locale::French));
    }

    public function testFallsBackToSourceContentWhenGeminiReturnsNonStringFields(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Plan', 'plan-id', 'account-id');
        $version = $plan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        $spanishTranslation = $sections[0]->translations()->getTranslationFor(Locale::Spanish);
        $this->assertInstanceOf(PreventionPlanSectionTranslation::class, $spanishTranslation);

        $this->geminiClient->setResponse([
            ['index' => 0, 'title' => 123, 'content' => 456],
            ['index' => 1, 'title' => 'Diagnosis', 'content' => 'Diagnosis content.'],
        ]);

        $this->sut->translate($version, Locale::English);

        $translation0 = $sections[0]->translations()->getTranslationFor(Locale::English);
        $this->assertInstanceOf(PreventionPlanSectionTranslation::class, $translation0);
        $this->assertSame($spanishTranslation->title(), $translation0->title());
        $this->assertSame($spanishTranslation->content(), $translation0->content());
    }
}
