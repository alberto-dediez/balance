<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration\Messenger;

use App\Shared\Infrastructure\Integration\Messenger\IncomingIntegrationMessage;
use App\Shared\Infrastructure\Integration\Messenger\IntegrationMessageSerializer;
use App\Shared\Infrastructure\Integration\Messenger\OutgoingIntegrationMessage;
use App\Shared\Infrastructure\Integration\Messenger\UndecodableIntegrationMessage;
use App\Tests\Unit\Shared\Infrastructure\Integration\IntegrationEnvelopeMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class IntegrationMessageSerializerTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../fixtures/platform-pinged-v1.json';

    public function testEncodeProducesTheContractFixture(): void
    {
        $encoded = (new IntegrationMessageSerializer())->encode(
            new Envelope(new OutgoingIntegrationMessage(IntegrationEnvelopeMother::platformPinged())),
        );

        $expected = json_encode(
            json_decode((string) file_get_contents(self::FIXTURE), false, 512, JSON_THROW_ON_ERROR),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $this->assertSame($expected, $encoded['body']);
        $this->assertSame([
            'content_type' => 'application/json',
            'x-integration-type' => 'platform.pinged',
            'x-integration-version' => '1',
            'x-integration-source' => 'waste',
        ], $encoded['headers'] ?? null);
    }

    public function testEncodedMessageContainsNoPhpClassName(): void
    {
        $encoded = (new IntegrationMessageSerializer())->encode(
            new Envelope(new OutgoingIntegrationMessage(IntegrationEnvelopeMother::platformPinged())),
        );

        $this->assertStringNotContainsString('App\\', $encoded['body']);

        foreach ($encoded['headers'] ?? [] as $name => $value) {
            $this->assertStringNotContainsString('App\\', $name);
            $this->assertStringNotContainsString('App\\', $value);
        }
    }

    public function testDecodeFixtureReturnsIncomingMessage(): void
    {
        $envelope = (new IntegrationMessageSerializer())->decode([
            'body' => (string) file_get_contents(self::FIXTURE),
            'headers' => [],
        ]);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(IncomingIntegrationMessage::class, $message);
        $this->assertEquals(IntegrationEnvelopeMother::platformPinged(), $message->envelope);
    }

    public function testDecodeBrokenJsonReturnsUndecodableMessage(): void
    {
        $envelope = (new IntegrationMessageSerializer())->decode(['body' => '{"id":', 'headers' => []]);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(UndecodableIntegrationMessage::class, $message);
        $this->assertSame('{"id":', $message->body);
        $this->assertNotSame('', $message->reason);
    }

    public function testDecodeIncompleteEnvelopeReturnsUndecodableMessage(): void
    {
        $data = IntegrationEnvelopeMother::platformPingedArray();
        unset($data['sequence']);

        $envelope = (new IntegrationMessageSerializer())->decode(['body' => (string) json_encode($data), 'headers' => []]);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(UndecodableIntegrationMessage::class, $message);
        $this->assertStringContainsString('sequence', $message->reason);
    }

    public function testEncodeOtherMessageThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new IntegrationMessageSerializer())->encode(new Envelope(new \stdClass()));
    }
}
