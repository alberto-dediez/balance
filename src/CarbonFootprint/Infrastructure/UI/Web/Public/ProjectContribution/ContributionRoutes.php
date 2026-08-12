<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution;

final readonly class ContributionRoutes
{
    /** @param array<string, string> $routeParams */
    public function __construct(
        public string $result,
        public string $choose,
        public string $project,
        public string $checkout,
        public string $return,
        public string $cancel,
        public string $certificate,
        public string $certificateEmail,
        public array $routeParams,
        public string $layout,
        public string $csrfTokenId,
    ) {}

    /** @return array<string, string> */
    public function contributionParams(string $contributionId): array
    {
        return ['token' => $this->routeParams['token'] ?? '', 'contributionId' => $contributionId];
    }
}
