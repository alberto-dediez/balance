<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Domain\Model\Account\AccountId;

class ProtocolMother
{
    public static function registeredProtocol(): Protocol
    {
        return self::fromName('protocol-name');
    }

    public static function fromName(string $name): Protocol
    {
        return self::from(
            name: $name,
            id: (string) ProtocolId::generate(),
            accountId: 'account-id',
        );
    }

    public static function fromNameAndId(string $name, string $id): Protocol
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: 'account-id',
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id',
        string $description = 'A protocol description',
        string $path = 'protocols/account-id/file.pdf',
    ): Protocol {
        $protocol = new Protocol(
            id: new ProtocolId($id),
        );
        $protocol->register(
            name: $name,
            description: $description,
            path: $path,
            accountId: new AccountId($accountId),
        );

        return $protocol;
    }

    public static function forAccount(string $accountId): Protocol
    {
        return self::from(
            name: 'protocol-' . rand(100, 999),
            id: (string) ProtocolId::generate(),
            accountId: $accountId,
        );
    }

    public static function random(): Protocol
    {
        return self::from(
            name: 'protocol-' . rand(100, 999),
            id: (string) ProtocolId::generate(),
        );
    }
}
