<?php

namespace App\Shared\Infrastructure\Email;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use App\Shared\Domain\Model\Email\EmailSent;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;

class SymfonyEmailSender implements EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $mailerFrom
    )
    {
        
    }

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
                content: $template
            )
        );
    }
}