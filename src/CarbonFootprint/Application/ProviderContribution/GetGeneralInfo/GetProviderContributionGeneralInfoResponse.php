<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\EventOperations\Domain\Model\Event\Event;

final class GetProviderContributionGeneralInfoResponse
{
    public function __construct(
        public readonly ProviderContribution $providerContribution,
        public readonly Event $event,
    ) {}
}
