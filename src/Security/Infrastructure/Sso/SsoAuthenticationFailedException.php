<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso;

final class SsoAuthenticationFailedException extends \RuntimeException
{
    public static function unverifiedEmail(string $provider): self
    {
        return new self(sprintf('The identity provider "%s" did not verify the email.', $provider));
    }

    public static function missingEmail(string $provider): self
    {
        return new self(sprintf('The identity provider "%s" did not return an email.', $provider));
    }

    public static function fromProvider(string $provider, \Throwable $previous): self
    {
        return new self(sprintf('The identity provider "%s" failed.', $provider), 0, $previous);
    }
}
