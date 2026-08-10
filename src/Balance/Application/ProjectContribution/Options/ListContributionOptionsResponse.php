<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Options;

final class ListContributionOptionsResponse
{
    /** @param array<int, ContributionOption> $options */
    public function __construct(public readonly array $options = []) {}

    public function isEmpty(): bool
    {
        return [] === $this->options;
    }

    public function find(string $projectId): ?ContributionOption
    {
        foreach ($this->options as $option) {
            if ((string) $option->project->id() === $projectId) {
                return $option;
            }
        }

        return null;
    }
}
