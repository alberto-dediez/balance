<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\ProviderContribution\List\ListProviderContributionsRequest;
use App\CarbonFootprint\Application\ProviderContribution\List\ListProviderContributionsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\EventOperations\Application\Provider\List\ListProvidersRequest;
use App\EventOperations\Application\Provider\List\ListProvidersService;
use App\Security\Domain\Model\User\User;

final class ProvidersStepView
{
    public function __construct(
        private ListProvidersService $listProviders,
        private ListProviderContributionsService $listProviderContributions,
    ) {}

    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, User $user): array
    {
        $accountId = $eventCarbonFootprint->accountId();

        $providersResponse = ($this->listProviders)(new ListProvidersRequest(accountId: $accountId, limit: 0), $user);
        $contributionsResponse = ($this->listProviderContributions)(new ListProviderContributionsRequest($eventCarbonFootprint->id()), $user);

        $assignedProviderIds = [];
        foreach ($contributionsResponse->providerContributions as $contribution) {
            if ($contribution->status()->isIgnored()) {
                continue;
            }

            $providerId = $contribution->providerId();

            if (null !== $providerId) {
                $assignedProviderIds[(string) $providerId] = true;
            }
        }

        $providersById = [];
        foreach ($providersResponse->providers as $provider) {
            $providersById[(string) $provider->id()] = $provider;
        }

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'providers' => $providersResponse->providers,
            'assignedProviderIds' => $assignedProviderIds,
            'contributions' => $contributionsResponse->providerContributions,
            'providersById' => $providersById,
        ];
    }
}
