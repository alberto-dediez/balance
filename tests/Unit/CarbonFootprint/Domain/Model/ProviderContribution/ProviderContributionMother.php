<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;

class ProviderContributionMother
{
    public static function registeredProviderContribution(): ProviderContribution
    {
        return self::from(
            eventCarbonFootprintId: (string) EventCarbonFootprintId::generate(),
            accountId: 'account-id',
            providerId: (string) ProviderId::generate(),
            id: (string) ProviderContributionId::generate(),
        );
    }

    public static function fromEventCarbonFootprintIdAndAccount(string $eventCarbonFootprintId, string $accountId): ProviderContribution
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            accountId: $accountId,
            providerId: (string) ProviderId::generate(),
            id: (string) ProviderContributionId::generate(),
        );
    }

    public static function fromEventCarbonFootprintIdAndProviderId(string $eventCarbonFootprintId, string $providerId): ProviderContribution
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            accountId: 'account-id',
            providerId: $providerId,
            id: (string) ProviderContributionId::generate(),
        );
    }

    public static function from(
        string $eventCarbonFootprintId,
        string $accountId,
        string $providerId,
        string $id,
        ?string $companyName = 'Provider Company',
    ): ProviderContribution {
        $providerContribution = new ProviderContribution(id: new ProviderContributionId($id));
        $providerContribution->register(
            eventCarbonFootprintId: new EventCarbonFootprintId($eventCarbonFootprintId),
            accountId: new AccountId($accountId),
            providerId: new ProviderId($providerId),
            token: ProviderContribution::generateToken(),
            companyName: $companyName,
        );

        return $providerContribution;
    }

    public static function random(): ProviderContribution
    {
        return self::registeredProviderContribution();
    }
}
