<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.integration.handler')]
interface IntegrationEventHandler
{
    public function supports(string $type, int $version): bool;

    public function handle(ReceivedIntegrationEvent $event, User $user): void;
}
