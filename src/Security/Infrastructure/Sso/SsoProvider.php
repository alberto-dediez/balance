<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('security.sso_provider')]
interface SsoProvider
{
    public function key(): string;

    public function label(): string;

    public function isEnabled(): bool;

    public function loginUrl(): string;

    /** @throws SsoAuthenticationFailedException */
    public function authenticate(Request $request): SsoIdentity;
}
