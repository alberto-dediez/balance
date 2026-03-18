<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailSent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SymfonyEmailSender implements EmailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
    ) {}

    /**
     * @param array<string,mixed>|null $params
     */
    public function __invoke(EmailRecipient $recipient, string $subject, string $template, ?array $params = null): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(new Address($recipient->email, $recipient->name))
            ->subject($subject)
            ->htmlTemplate($template)
        ;

        if (null !== $params && count($params) > 0) {
            $email->context($params);
        }

        $this->mailer->send($email);

        DomainEventPublisher::instance()->publish(
            new EmailSent(
                recipient: $recipient,
                subject: $subject,
                content: $template,
            ),
        );
    }
}
