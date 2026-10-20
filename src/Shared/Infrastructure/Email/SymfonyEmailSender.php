<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailSendingFailed;
use App\Shared\Domain\Model\Email\EmailSent;
use App\Shared\Domain\Model\Email\EmailTrackingId;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;

class SymfonyEmailSender implements EmailSender
{
    private readonly string $provider;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $mailerFrom,
        string $mailerDsn,
    ) {
        $this->provider = explode('+', Dsn::fromString($mailerDsn)->getScheme())[0];
    }

    /**
     * @param array<string,mixed>|null $params
     */
    public function __invoke(EmailRecipient $recipient, string $subject, string $template, ?array $params = null): EmailTrackingId
    {
        $trackingId = EmailTrackingId::generate();

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(new Address($recipient->email, $recipient->name))
            ->subject($subject)
            ->htmlTemplate($template)
        ;

        if (null !== $params && count($params) > 0) {
            $email->context($params);
        }

        $email->getHeaders()->add(new MetadataHeader('email_log_id', (string) $trackingId));

        try {
            $sent = $this->transport->send($email);
        } catch (\Throwable $e) {
            DomainEventPublisher::instance()->publish(
                new EmailSendingFailed(
                    trackingId: $trackingId,
                    recipient: $recipient,
                    subject: $subject,
                    content: $template,
                    provider: $this->provider,
                    reason: $e->getMessage(),
                ),
            );

            throw $e;
        }

        DomainEventPublisher::instance()->publish(
            new EmailSent(
                recipient: $recipient,
                subject: $subject,
                content: $template,
                trackingId: $trackingId,
                provider: $this->provider,
                providerMessageId: $sent?->getMessageId(),
            ),
        );

        return $trackingId;
    }
}
