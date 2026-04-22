<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Infrastructure\AI\Gemini\GenerateMeasurementSectionPrompt;
use App\FoodLoss\Infrastructure\AI\GeminiSectionsGenerator;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryReasonRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class GeminiSectionsGeneratorTest extends TestCase
{
    private const GEMINI_TITLE = 'Generated Section Title';
    private const GEMINI_CONTENT = '## Introduction\n\nThis is the generated content.';

    public function testGeneratesFiveSectionsForEmptyPlan(): void
    {
        $geminiClient = new InMemoryGeminiClient(['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT]);
        $sut = $this->buildGenerator($geminiClient);

        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: []);
        $version = $plan->currentPlanVersion();

        $sut->generate($version);

        $this->assertCount(5, $version->sections()->toArray());
    }

    public function testGeneratesFiveSectionsForPlanWithPropertyAndCriticalPoints(): void
    {
        $accountId = (string) AccountId::generate();
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(AccountMother::fromNameAndId('ACME Hotels', $accountId));

        $propertyRepository = new InMemoryPropertyRepository();
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Central', (string) $propertyId));

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(
            CriticalPointMother::fromNameAndAccountAndProperty('Kitchen waste', AccountId::fromString($accountId), $propertyId),
        );

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $geminiClient = new InMemoryGeminiClient(['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT]);

        $sut = new GeminiSectionsGenerator(
            $accountRepository,
            $propertyRepository,
            $criticalPointRepository,
            $actionRepository,
            new InMemoryReasonRepository(),
            $geminiClient,
        );

        $plan = $this->buildPlan(accountId: $accountId, propertyIds: [$propertyId]);
        $plan->addProposedAction($propertyId, new ActionId($actionId), new CriticalPointId($criticalPointId));
        $version = $plan->currentPlanVersion();

        $sut->generate($version);

        $this->assertCount(5, $version->sections()->toArray());
    }

    public function testSectionsContainTitleAndContentFromGeminiResponse(): void
    {
        $geminiClient = new InMemoryGeminiClient(['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT]);
        $sut = $this->buildGenerator($geminiClient);

        $version = $this->buildPlan(accountId: 'account-id', propertyIds: [])->currentPlanVersion();
        $sut->generate($version);

        foreach ($version->sections()->toArray() as $section) {
            $this->assertSame(self::GEMINI_TITLE, $section->title());
            $this->assertSame(self::GEMINI_CONTENT, $section->content());
        }
    }

    public function testFirstPromptIsIntroductionAndLastIsMeasurement(): void
    {
        $propertyId = PropertyId::generate();
        $propertyRepository = new InMemoryPropertyRepository();
        $propertyRepository->add(PropertyMother::fromNameAndId('Test Hotel', (string) $propertyId));

        $geminiClient = new InMemoryGeminiClient(['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT]);

        $sut = new GeminiSectionsGenerator(
            new InMemoryAccountRepository(),
            $propertyRepository,
            new InMemoryCriticalPointRepository(),
            new InMemoryActionRepository(),
            new InMemoryReasonRepository(),
            $geminiClient,
        );

        $plan = $this->buildPlan(accountId: 'account-id', propertyIds: [$propertyId]);
        $sut->generate($plan->currentPlanVersion());

        $this->assertInstanceOf(GenerateMeasurementSectionPrompt::class, $geminiClient->lastPrompt());
    }

    public function testClearsExistingSectionsBeforeGenerating(): void
    {
        $geminiClient = new InMemoryGeminiClient(['title' => self::GEMINI_TITLE, 'content' => self::GEMINI_CONTENT]);
        $sut = $this->buildGenerator($geminiClient);

        $version = $this->buildPlan(accountId: 'account-id', propertyIds: [])->currentPlanVersion();
        $sut->generate($version);
        $this->assertCount(5, $version->sections()->toArray());

        $sut->generate($version);
        $this->assertCount(5, $version->sections()->toArray());
    }

    public function testHandlesMissingGeminiFieldsGracefully(): void
    {
        $geminiClient = new InMemoryGeminiClient([]);
        $sut = $this->buildGenerator($geminiClient);

        $version = $this->buildPlan(accountId: 'account-id', propertyIds: [])->currentPlanVersion();
        $sut->generate($version);

        $sections = $version->sections()->toArray();
        $this->assertCount(5, $sections);
        foreach ($sections as $section) {
            $this->assertSame('', $section->title());
            $this->assertSame('', $section->content());
        }
    }

    private function buildGenerator(InMemoryGeminiClient $geminiClient): GeminiSectionsGenerator
    {
        return new GeminiSectionsGenerator(
            new InMemoryAccountRepository(),
            new InMemoryPropertyRepository(),
            new InMemoryCriticalPointRepository(),
            new InMemoryActionRepository(),
            new InMemoryReasonRepository(),
            $geminiClient,
        );
    }

    /** @param PropertyId[] $propertyIds */
    private function buildPlan(string $accountId, array $propertyIds): PreventionPlan
    {
        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Test Prevention Plan',
            propertyIds: $propertyIds,
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id'),
        );

        return $plan;
    }
}
