<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Webhook;

use Symfony\Component\Mailer\Transport\Dsn;

class SendgridSignatureVerifier
{
    private const string SENDGRID_PROVIDER = 'sendgrid';

    private readonly string $provider;

    public function __construct(
        #[\SensitiveParameter]
        private readonly string $webhookSecret,
        #[\SensitiveParameter]
        string $mailerDsn,
    ) {
        $this->provider = explode('+', Dsn::fromString($mailerDsn)->getScheme())[0];
    }

    public function isSecretMissing(): bool
    {
        return '' === $this->webhookSecret && self::SENDGRID_PROVIDER === $this->provider;
    }

    public function verify(?string $signature, ?string $timestamp, string $payload): bool
    {
        if ($this->isSecretMissing()) {
            return false;
        }

        if ('' === $this->webhookSecret) {
            return true;
        }

        if (null === $signature || '' === $signature || null === $timestamp || '' === $timestamp) {
            return false;
        }

        $pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($this->webhookSecret, 64, "\n") . "-----END PUBLIC KEY-----\n";

        $publicKey = openssl_pkey_get_public($pemKey);

        if (false === $publicKey) {
            return false;
        }

        return 1 === openssl_verify($timestamp . $payload, base64_decode($signature), $publicKey, \OPENSSL_ALGO_SHA256);
    }
}
