<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Infrastructure\AI\GeminiProposedActionsLoader;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use PHPUnit\Framework\TestCase;

class GeminiProposedActionsLoaderTest extends TestCase
{
    public function testReturnsEmptyWhenNoCriticalPointsForProperty(): void
    {
        $propertyId = PropertyId::generate();
        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::random());

        $geminiClient = new InMemoryGeminiClient();
        $sut = new GeminiProposedActionsLoader(
            new InMemoryCriticalPointRepository(),
            $actionRepository,
            $geminiClient,
        );

        $this->assertEmpty($sut->suggestFor($propertyId));
        $this->assertNull($geminiClient->lastPrompt());
    }

    public function testReturnsEmptyWhenNoActionsExist(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(CriticalPointMother::fromNameAndAccountAndProperty('cp', AccountId::generate(), $propertyId));

        $geminiClient = new InMemoryGeminiClient();
        $sut = new GeminiProposedActionsLoader(
            $criticalPointRepository,
            new InMemoryActionRepository(),
            $geminiClient,
        );

        $this->assertEmpty($sut->suggestFor($propertyId));
        $this->assertNull($geminiClient->lastPrompt());
    }

    public function testReturnsSuggestionsFromGeminiResponse(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(
            CriticalPointMother::from('cp', $criticalPointId, propertyId: $propertyId),
        );

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $geminiClient = new InMemoryGeminiClient([
            ['criticalPointId' => $criticalPointId, 'actionIds' => [$actionId]],
        ]);

        $sut = new GeminiProposedActionsLoader($criticalPointRepository, $actionRepository, $geminiClient);

        $suggestions = $sut->suggestFor($propertyId);

        $this->assertCount(1, $suggestions);
        $this->assertSame($criticalPointId, (string) $suggestions[0]->criticalPointId);
        $this->assertSame($actionId, (string) $suggestions[0]->actionId);
    }

    public function testFiltersOutHallucinatedCriticalPointIds(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(CriticalPointMother::from('cp', $criticalPointId, propertyId: $propertyId));

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $geminiClient = new InMemoryGeminiClient([
            ['criticalPointId' => 'non-existent-critical-point-id', 'actionIds' => [$actionId]],
        ]);

        $sut = new GeminiProposedActionsLoader($criticalPointRepository, $actionRepository, $geminiClient);

        $this->assertEmpty($sut->suggestFor($propertyId));
    }

    public function testFiltersOutHallucinatedActionIds(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(CriticalPointMother::from('cp', $criticalPointId, propertyId: $propertyId));

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $geminiClient = new InMemoryGeminiClient([
            ['criticalPointId' => $criticalPointId, 'actionIds' => ['non-existent-action-id']],
        ]);

        $sut = new GeminiProposedActionsLoader($criticalPointRepository, $actionRepository, $geminiClient);

        $this->assertEmpty($sut->suggestFor($propertyId));
    }

    public function testReturnsMultipleSuggestionsAcrossMultipleCriticalPoints(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointId1 = (string) CriticalPointId::generate();
        $criticalPointId2 = (string) CriticalPointId::generate();
        $actionId1 = (string) ActionId::generate();
        $actionId2 = (string) ActionId::generate();
        $actionId3 = (string) ActionId::generate();

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(CriticalPointMother::from('cp1', $criticalPointId1, propertyId: $propertyId));
        $criticalPointRepository->add(CriticalPointMother::from('cp2', $criticalPointId2, propertyId: $propertyId));

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId1));
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::HumanDonation, $actionId2));
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Composting, $actionId3));

        $geminiClient = new InMemoryGeminiClient([
            ['criticalPointId' => $criticalPointId1, 'actionIds' => [$actionId1, $actionId2]],
            ['criticalPointId' => $criticalPointId2, 'actionIds' => [$actionId3]],
        ]);

        $sut = new GeminiProposedActionsLoader($criticalPointRepository, $actionRepository, $geminiClient);

        $suggestions = $sut->suggestFor($propertyId);

        $this->assertCount(3, $suggestions);
    }

    public function testReturnsEmptyWhenGeminiSuggestsNoActionsForAnyCriticalPoint(): void
    {
        $propertyId = PropertyId::generate();
        $criticalPointId = (string) CriticalPointId::generate();
        $actionId = (string) ActionId::generate();

        $criticalPointRepository = new InMemoryCriticalPointRepository();
        $criticalPointRepository->add(CriticalPointMother::from('cp', $criticalPointId, propertyId: $propertyId));

        $actionRepository = new InMemoryActionRepository();
        $actionRepository->add(ActionMother::fromPriorityAndId(Priority::Prevention, $actionId));

        $geminiClient = new InMemoryGeminiClient([
            ['criticalPointId' => $criticalPointId, 'actionIds' => []],
        ]);

        $sut = new GeminiProposedActionsLoader($criticalPointRepository, $actionRepository, $geminiClient);

        $this->assertEmpty($sut->suggestFor($propertyId));
    }
}
