<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Webhook;

use App\Security\Application\EmailLog\Track\EmailTrackingEventType;
use App\Security\Application\EmailLog\Track\TrackEmailEventRequest;
use App\Security\Application\EmailLog\Track\TrackEmailEventService;
use App\Security\Infrastructure\Webhook\SendgridSignatureVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SendgridWebhookController extends AbstractController
{
    public function __construct(
        private readonly TrackEmailEventService $trackEmailEvent,
        private readonly SendgridSignatureVerifier $signatureVerifier,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(path: '/webhook/sendgrid', name: 'webhook_sendgrid', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $events = $request->toArray();
        } catch (JsonException) {
            return new Response('Payload is malformed.', Response::HTTP_NOT_ACCEPTABLE);
        }

        if (
            !isset($events[0]['email'])
            || !isset($events[0]['timestamp'])
            || !isset($events[0]['event'])
            || !isset($events[0]['sg_event_id'])
        ) {
            return new Response('Payload is malformed.', Response::HTTP_NOT_ACCEPTABLE);
        }

        $isVerified = $this->signatureVerifier->verify(
            $request->headers->get('X-Twilio-Email-Event-Webhook-Signature'),
            $request->headers->get('X-Twilio-Email-Event-Webhook-Timestamp'),
            $request->getContent(),
        );

        if (!$isVerified) {
            if ($this->signatureVerifier->isSecretMissing()) {
                $this->logger->error('SENDGRID_WEBHOOK_SECRET vacío con proveedor sendgrid');
            }

            return new Response('Signature is wrong.', Response::HTTP_NOT_ACCEPTABLE);
        }

        foreach ($this->chronological($events) as $event) {
            $this->track($event);
        }

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * @param array<mixed> $event
     */
    private function track(array $event): void
    {
        $type = EmailTrackingEventType::tryFrom(is_string($event['event'] ?? null) ? $event['event'] : '');
        $emailLogId = is_string($event['email_log_id'] ?? null) && '' !== $event['email_log_id'] ? $event['email_log_id'] : null;
        $providerMessageId = $this->providerMessageIdOf($event);

        if (!$type instanceof EmailTrackingEventType || (null === $emailLogId && null === $providerMessageId)) {
            $this->logger->warning('Evento de SendGrid ignorado', [
                'event' => $event['event'] ?? null,
                'email_log_id' => $emailLogId,
                'sg_message_id' => $event['sg_message_id'] ?? null,
                'sg_event_id' => $event['sg_event_id'] ?? null,
            ]);

            return;
        }

        if (EmailTrackingEventType::Opened === $type && true === ($event['sg_machine_open'] ?? false)) {
            return;
        }

        $reason = $event['reason'] ?? ($event['response'] ?? null);

        $trackEmailEvent = $this->trackEmailEvent;

        try {
            $response = $trackEmailEvent(
                new TrackEmailEventRequest(
                    emailLogId: $emailLogId,
                    type: $type,
                    occurredOn: new \DateTimeImmutable('@' . (is_scalar($event['timestamp'] ?? null) ? (string) $event['timestamp'] : '')),
                    reason: is_scalar($reason) ? (string) $reason : null,
                    providerMessageId: $providerMessageId,
                ),
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Error al procesar un evento de SendGrid', [
                'event' => $type->value,
                'email_log_id' => $emailLogId,
                'sg_message_id' => $event['sg_message_id'] ?? null,
                'sg_event_id' => $event['sg_event_id'] ?? null,
                'exception' => $exception,
            ]);

            return;
        }

        if (!$response->tracked) {
            $this->logger->warning('Evento de SendGrid sin email registrado', [
                'event' => $type->value,
                'email_log_id' => $emailLogId,
                'sg_message_id' => $event['sg_message_id'] ?? null,
            ]);
        }
    }

    /**
     * @param array<mixed> $events
     *
     * @return array<array<mixed>>
     */
    private function chronological(array $events): array
    {
        $events = array_values(array_filter($events, is_array(...)));

        usort($events, fn(array $a, array $b): int => [$this->timestampOf($a), $this->isEngagement($a)] <=> [$this->timestampOf($b), $this->isEngagement($b)]);

        return $events;
    }

    /**
     * @param array<mixed> $event
     */
    private function timestampOf(array $event): int
    {
        return is_numeric($event['timestamp'] ?? null) ? (int) $event['timestamp'] : 0;
    }

    /**
     * @param array<mixed> $event
     */
    private function isEngagement(array $event): bool
    {
        return in_array($event['event'] ?? null, [EmailTrackingEventType::Opened->value, EmailTrackingEventType::Clicked->value], true);
    }

    /**
     * @param array<mixed> $event
     */
    private function providerMessageIdOf(array $event): ?string
    {
        $sgMessageId = $event['sg_message_id'] ?? null;

        if (!is_string($sgMessageId) || '' === $sgMessageId) {
            return null;
        }

        $providerMessageId = explode('.', $sgMessageId, 2)[0];

        return '' === $providerMessageId ? null : $providerMessageId;
    }
}
