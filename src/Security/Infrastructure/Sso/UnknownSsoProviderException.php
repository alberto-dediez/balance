<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso;

final class UnknownSsoProviderException extends \RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Unknown SSO provider "%s".', $key));
    }
}
