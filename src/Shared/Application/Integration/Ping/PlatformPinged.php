<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration\Ping;

use App\Shared\Application\Integration\IntegrationEvent;

final readonly class PlatformPinged implements IntegrationEvent
{
    public function __construct(public string $message) {}

    public static function type(): string
    {
        return 'platform.pinged';
    }

    public static function version(): int
    {
        return 1;
    }

    public function aggregateId(): ?string
    {
        return null;
    }

    public function accountRef(): ?string
    {
        return null;
    }

    public function payload(): array
    {
        return ['message' => $this->message];
    }
}
