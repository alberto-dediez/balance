<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SendgridTrackingSettingsHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    private const string MAIL_SEND_PATH = '/v3/mail/send';

    public function __construct(
        HttpClientInterface $client,
        private readonly bool $clickTracking,
    ) {
        $this->client = $client;
    }

    /**
     * @param array<string,mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ('POST' === $method && str_ends_with($url, self::MAIL_SEND_PATH) && is_array($options['json'] ?? null)) {
            $options['json']['tracking_settings']['click_tracking'] = [
                'enable' => $this->clickTracking,
                'enable_text' => $this->clickTracking,
            ];
        }

        return $this->client->request($method, $url, $options);
    }
}
