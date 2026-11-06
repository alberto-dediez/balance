<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Email;

use App\Shared\Infrastructure\Email\SendgridTrackingSettingsHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridApiTransport;
use Symfony\Component\Mime\Email;

class SendgridTrackingSettingsHttpClientTest extends TestCase
{
    /**
     * @return iterable<string, array{bool}>
     */
    public static function clickTrackingProvider(): iterable
    {
        yield 'disabled' => [false];
        yield 'enabled' => [true];
    }

    /**
     * @dataProvider clickTrackingProvider
     */
    public function testMailSendPayloadCarriesClickTrackingSetting(bool $clickTracking): void
    {
        /** @var array<string,mixed> $payload */
        $payload = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$payload): MockResponse {
            $payload = json_decode($options['body'], true);

            return new MockResponse('', ['http_code' => 202, 'response_headers' => ['x-message-id' => 'abc']]);
        });

        $transport = new SendgridApiTransport('KEY', new SendgridTrackingSettingsHttpClient($mock, $clickTracking));
        $transport->send(
            (new Email())
                ->from('from@example.com')
                ->to('to@example.com')
                ->subject('Subject')
                ->html('<a href="https://example.com">link</a>'),
        );

        $this->assertSame(
            ['click_tracking' => ['enable' => $clickTracking, 'enable_text' => $clickTracking]],
            $payload['tracking_settings'],
        );
    }

    public function testOtherRequestsAreNotModified(): void
    {
        /** @var array<string,mixed> $received */
        $received = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$received): MockResponse {
            $received = json_decode($options['body'], true);

            return new MockResponse('{}');
        });

        $client = new SendgridTrackingSettingsHttpClient($mock, true);
        $client->request('POST', 'https://api.sendgrid.com/v3/other', ['json' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar'], $received);
    }
}
