<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration;

interface IntegrationEvent
{
    public static function type(): string;

    public static function version(): int;

    public function aggregateId(): ?string;

    public function accountRef(): ?string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
