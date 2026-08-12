<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class PublicLandingMother
{
    public static function registeredPublicLanding(): PublicLanding
    {
        return self::forProperty((string) PropertyId::generate(), 'account-id');
    }

    public static function forProperty(string $propertyId, string $accountId = 'account-id'): PublicLanding
    {
        return self::from(PublicLandingOwnerType::Property, $propertyId, $accountId);
    }

    public static function forEvent(string $eventId, string $accountId = 'account-id'): PublicLanding
    {
        return self::from(PublicLandingOwnerType::Event, $eventId, $accountId);
    }

    public static function from(
        PublicLandingOwnerType $ownerType,
        string $ownerId,
        string $accountId = 'account-id',
        ?string $id = null,
    ): PublicLanding {
        $publicLanding = new PublicLanding(new PublicLandingId($id ?? (string) PublicLandingId::generate()));
        $publicLanding->register(
            ownerType: $ownerType,
            ownerId: $ownerId,
            accountId: new AccountId($accountId),
        );

        return $publicLanding;
    }

    public static function activeForEvent(string $eventId, string $accountId = 'account-id'): PublicLanding
    {
        $publicLanding = self::forEvent($eventId, $accountId);
        $publicLanding->update(
            active: true,
            title: 'Bienvenido',
            subtitle: 'Calcula tu huella',
            intro: 'Este evento mide su impacto.',
            heroImage: null,
            ctaLabel: 'Empezar',
        );

        return $publicLanding;
    }

    public static function random(): PublicLanding
    {
        return self::forEvent((string) EventId::generate());
    }
}
