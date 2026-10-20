<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Email;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSendingFailed;
use App\Shared\Domain\Model\Email\EmailSent;
use App\Shared\Infrastructure\Email\SymfonyEmailSender;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class SymfonyEmailSenderFailureTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testFailingTransportPublishesEmailSendingFailedAndRethrows(): void
    {
        $transport = new class implements TransportInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new \RuntimeException('transport down');
            }

            public function __toString(): string
            {
                return 'failing://default';
            }
        };

        $sut = new SymfonyEmailSender($transport, 'from@email.com', 'sendgrid+api://KEY@default');
        $recipient = new EmailRecipient('name of recipient', 'recipient@email.com');

        try {
            $sut($recipient, 'about email', '@shared/layout/email.html.twig');
            $this->fail('The transport exception must be propagated');
        } catch (\RuntimeException $e) {
            $this->assertSame('transport down', $e->getMessage());
        }

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EmailSendingFailed::class, $lastEvent);
        $this->assertEquals($recipient, $lastEvent->recipient());
        $this->assertSame('sendgrid', $lastEvent->provider());
        $this->assertSame('transport down', $lastEvent->reason());
        $this->assertSame('@shared/layout/email.html.twig', $lastEvent->content());
    }

    public function testSentMessagePublishesProviderMessageIdAndAddsTrackingHeader(): void
    {
        $transport = new class implements TransportInterface {
            public ?RawMessage $message = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): SentMessage
            {
                $this->message = $message;

                $sentMessage = new SentMessage(
                    (new Email())->from('from@email.com')->to('recipient@email.com')->text('body'),
                    new Envelope(new Address('from@email.com'), [new Address('recipient@email.com')]),
                );
                $sentMessage->setMessageId('provider-message-id');

                return $sentMessage;
            }

            public function __toString(): string
            {
                return 'spy://default';
            }
        };

        $sut = new SymfonyEmailSender($transport, 'from@email.com', 'smtp://mailer:1025');

        $trackingId = $sut(new EmailRecipient('name of recipient', 'recipient@email.com'), 'about email', '@shared/layout/email.html.twig');

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EmailSent::class, $lastEvent);
        $this->assertSame('provider-message-id', $lastEvent->providerMessageId());
        $this->assertSame('smtp', $lastEvent->provider());
        $this->assertTrue($trackingId->equals($lastEvent->trackingId()));

        $this->assertInstanceOf(Message::class, $transport->message);
        $header = $transport->message->getHeaders()->get('X-Metadata-email_log_id');
        $this->assertNotNull($header);
        $this->assertSame((string) $trackingId, $header->getBodyAsString());
    }
}
