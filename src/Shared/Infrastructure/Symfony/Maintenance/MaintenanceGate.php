<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Maintenance;

final class MaintenanceGate
{
    public const string FLAG_FILE = 'var/maintenance.lock';
    public const string PAGE_FILE = 'templates/maintenance.html';
    public const int RETRY_AFTER_SECONDS = 30;

    private const string FALLBACK_BODY = 'Estamos en mantenimiento';

    /** @var string[] */
    private const array ALLOWED_PATHS = ['/webhooks/deployments'];

    public static function guard(string $projectDir): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url(\is_string($requestUri) ? $requestUri : '/', PHP_URL_PATH);

        $response = self::responseFor($projectDir, \is_string($path) ? $path : '/');

        if (null === $response) {
            return;
        }

        http_response_code($response['status']);

        foreach ($response['headers'] as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $response['body'];

        exit;
    }

    /** @return array{status: int, headers: array<string, string>, body: string}|null */
    public static function responseFor(string $projectDir, string $path): ?array
    {
        if (!file_exists($projectDir . '/' . self::FLAG_FILE)) {
            return null;
        }

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return null;
            }
        }

        $headers = [
            'Retry-After' => (string) self::RETRY_AFTER_SECONDS,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];

        if (str_starts_with($path, '/api')) {
            return [
                'status' => 503,
                'headers' => ['Content-Type' => 'application/json'] + $headers,
                'body' => (string) json_encode(['status' => 'maintenance']),
            ];
        }

        $pageFile = $projectDir . '/' . self::PAGE_FILE;
        $page = is_readable($pageFile) ? file_get_contents($pageFile) : false;

        return [
            'status' => 503,
            'headers' => ['Content-Type' => 'text/html; charset=UTF-8'] + $headers,
            'body' => false === $page ? self::FALLBACK_BODY : $page,
        ];
    }
}
