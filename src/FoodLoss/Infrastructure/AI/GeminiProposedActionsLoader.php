<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionsLoader;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionSuggestion;
use App\FoodLoss\Infrastructure\AI\Gemini\SuggestActionsPrompt;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;

class GeminiProposedActionsLoader implements ProposedActionsLoader
{
    public function __construct(
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly ActionRepository $actionRepository,
        private readonly GeminiClient $geminiClient,
    ) {}

    /** @return ProposedActionSuggestion[] */
    public function suggestFor(PropertyId $propertyId): array
    {
        $criticalPoints = $this->criticalPointRepository->findByFilter(
            new CriticalPointFilter(propertyId: $propertyId, limit: 1000),
        );

        $actions = $this->actionRepository->findByFilter(
            new ActionFilter(limit: 1000),
        );

        if ([] === $criticalPoints || [] === $actions) {
            return [];
        }

        $response = $this->geminiClient->send(new SuggestActionsPrompt($criticalPoints, $actions));

        return $this->mapToSuggestions($response, $criticalPoints, $actions);
    }

    /**
     * @param array<mixed>    $response
     * @param CriticalPoint[] $criticalPoints
     * @param Action[]        $actions
     *
     * @return ProposedActionSuggestion[]
     */
    private function mapToSuggestions(array $response, array $criticalPoints, array $actions): array
    {
        $validCriticalPointIds = array_flip(array_map(static fn(CriticalPoint $criticalPoint): string => (string) $criticalPoint->id(), $criticalPoints));
        $validActionIds = array_flip(array_map(static fn(Action $action): string => (string) $action->id(), $actions));
        $suggestions = [];

        foreach ($response as $item) {
            $criticalPointId = $item['criticalPointId'] ?? null;

            if (!is_string($criticalPointId) || !isset($validCriticalPointIds[$criticalPointId])) {
                continue;
            }

            foreach ($item['actionIds'] ?? [] as $actionId) {
                if (!is_string($actionId) || !isset($validActionIds[$actionId])) {
                    continue;
                }

                $suggestions[] = new ProposedActionSuggestion(
                    actionId: ActionId::fromString($actionId),
                    criticalPointId: CriticalPointId::fromString($criticalPointId),
                );
            }
        }

        return $suggestions;
    }
}
