<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\Model\Provider\Contact;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Address;

class ProviderMother
{
    public static function registeredProvider(): Provider
    {
        return self::fromName('Provider');
    }

    public static function fromName(string $name): Provider
    {
        return self::from(
            name: $name,
            id: (string) ProviderId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Provider
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameAndAccountAndId(string $name, string $accountId, string $id): Provider
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $vatNumber = 'B12345678',
        ?string $accountId = null,
    ): Provider {
        $provider = new Provider(
            id: new ProviderId($id),
        );
        $provider->register(
            name: $name,
            vatNumber: $vatNumber,
            address: new Address(),
            contact: Contact::fromEmail(email: 'provider-' . $id . '@example.com'),
            accountId: null !== $accountId ? new AccountId($accountId) : null,
        );

        return $provider;
    }

    public static function random(): Provider
    {
        return self::from(
            name: 'provider-' . rand(100, 999),
            id: (string) ProviderId::generate(),
        );
    }
}
