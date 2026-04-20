<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI;

use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionsLoader;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionSuggestion;
use App\Property\Domain\Model\Property\PropertyId;

class FakeProposedActionsLoader implements ProposedActionsLoader
{
    /**
     * @param array<string, ProposedActionSuggestion[]> $suggestions
     * @param string[]                                  $calledWith
     */
    public function __construct(private array $suggestions = [], private array $calledWith = []) {}

    /** @return ProposedActionSuggestion[] */
    public function suggestFor(PropertyId $propertyId): array
    {
        $this->calledWith[] = (string) $propertyId;

        return $this->suggestions[(string) $propertyId] ?? [];
    }

    /**
     * @return string[]
     */
    public function calledWith(): array
    {
        return $this->calledWith;
    }
}
