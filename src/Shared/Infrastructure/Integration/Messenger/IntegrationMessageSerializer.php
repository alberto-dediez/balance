<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration\Messenger;

use App\Shared\Infrastructure\Integration\IntegrationEnvelope;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class IntegrationMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return new Envelope(new UndecodableIntegrationMessage($body, 'The body is not a JSON object'));
            }

            return new Envelope(new IncomingIntegrationMessage(IntegrationEnvelope::fromArray($data)));
        } catch (\Throwable $exception) {
            return new Envelope(new UndecodableIntegrationMessage($body, $exception->getMessage()));
        }
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        if (!$message instanceof OutgoingIntegrationMessage) {
            throw new \InvalidArgumentException(sprintf('Only outgoing integration messages can be encoded, "%s" given', get_debug_type($message)));
        }

        $integrationEnvelope = $message->envelope;

        return [
            'body' => json_encode($integrationEnvelope->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'headers' => [
                'content_type' => 'application/json',
                'x-integration-type' => $integrationEnvelope->type,
                'x-integration-version' => (string) $integrationEnvelope->version,
                'x-integration-source' => $integrationEnvelope->source,
            ],
        ];
    }
}
