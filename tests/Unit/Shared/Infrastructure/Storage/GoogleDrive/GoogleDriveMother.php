<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Storage\GoogleDrive;

use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveAccessTokenProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleDriveMother
{
    public const string CLIENT_EMAIL = 'hydra@project.iam.gserviceaccount.com';

    public const string TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public const string TOKEN = 'ya29.test-token';

    private static ?string $privateKey = null;

    private static ?string $publicKey = null;

    /**
     * @param array<string, mixed>|null $credentials
     */
    public static function credentialsFile(?array $credentials = null): string
    {
        return self::file((string) json_encode($credentials ?? self::credentials()));
    }

    /**
     * @return array<string, string>
     */
    public static function credentials(): array
    {
        return [
            'type' => 'service_account',
            'client_email' => self::CLIENT_EMAIL,
            'private_key' => self::privateKey(),
            'token_uri' => self::TOKEN_URI,
        ];
    }

    public static function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'google-drive');

        if (false === $path) {
            throw new \RuntimeException('Unable to create a temporary file');
        }

        file_put_contents($path, $contents);

        return $path;
    }

    public static function tokenProviderWithCachedToken(HttpClientInterface $httpClient): GoogleDriveAccessTokenProvider
    {
        $cache = new ArrayAdapter();

        $item = $cache->getItem('google_drive.access_token.' . sha1(self::CLIENT_EMAIL));
        $item->set(self::TOKEN);
        $cache->save($item);

        return new GoogleDriveAccessTokenProvider($httpClient, $cache, self::credentialsFile());
    }

    public static function privateKey(): string
    {
        self::generateKeys();

        return (string) self::$privateKey;
    }

    public static function publicKey(): string
    {
        self::generateKeys();

        return (string) self::$publicKey;
    }

    private static function generateKeys(): void
    {
        if (null !== self::$privateKey) {
            return;
        }

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if (false === $key || !openssl_pkey_export($key, $privateKey)) {
            throw new \RuntimeException('Unable to generate a RSA key');
        }

        $details = openssl_pkey_get_details($key);

        if (false === $details) {
            throw new \RuntimeException('Unable to read the RSA key');
        }

        self::$privateKey = $privateKey;
        self::$publicKey = $details['key'];
    }
}
