<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Event\EmailLog;

use App\Security\Application\EmailLog\Register\RegisterEmailLogService;
use App\Security\Domain\Event\EmailLog\RecordEmailLogOnEmailSent;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRegistered;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryEmailLogRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSendingFailed;
use App\Shared\Domain\Model\Email\EmailSent;
use App\Shared\Domain\Model\Email\EmailTrackingId;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RecordEmailLogOnEmailSentTest extends TestCase
{
    private InMemoryEmailLogRepository $repository;

    private RecordEmailLogOnEmailSent $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = EmailLogRepositoryMother::inMemory();
        $this->sut = new RecordEmailLogOnEmailSent(new RegisterEmailLogService($this->repository), new NullLogger());
    }

    public function testEmailSentRegistersSentEmailLogWithSameId(): void
    {
        $trackingId = EmailTrackingId::generate();

        DomainEventPublisher::instance()->publish(new EmailSent(
            recipient: new EmailRecipient('Recipient', 'recipient@example.com'),
            subject: 'Asunto',
            content: '@shared/layout/email.html.twig',
            trackingId: $trackingId,
            provider: 'sendgrid',
            providerMessageId: 'message-id',
        ));

        $emailLog = $this->repository->find(new EmailLogId((string) $trackingId));

        $this->assertNotNull($emailLog);
        $this->assertEquals(EmailLogStatus::Sent, $emailLog->status());
        $this->assertEquals('sendgrid', $emailLog->provider());
        $this->assertEquals('message-id', $emailLog->providerMessageId());
        $this->assertEquals('recipient@example.com', $emailLog->recipientEmail());
        $this->assertEquals('Recipient', $emailLog->recipientName());
        $this->assertEquals('@shared/layout/email.html.twig', $emailLog->template());
    }

    public function testEmailSendingFailedRegistersFailedEmailLogWithReason(): void
    {
        $trackingId = EmailTrackingId::generate();

        DomainEventPublisher::instance()->publish(new EmailSendingFailed(
            trackingId: $trackingId,
            recipient: new EmailRecipient('Recipient', 'recipient@example.com'),
            subject: 'Asunto',
            content: '@shared/layout/email.html.twig',
            provider: 'smtp',
            reason: 'transport down',
        ));

        $emailLog = $this->repository->find(new EmailLogId((string) $trackingId));

        $this->assertNotNull($emailLog);
        $this->assertEquals(EmailLogStatus::Failed, $emailLog->status());
        $this->assertEquals('transport down', $emailLog->statusReason());
        $this->assertNull($emailLog->providerMessageId());
    }

    public function testItIsNotSubscribedToOtherEvents(): void
    {
        $event = new EmailLogRegistered(
            emailLogId: EmailLogId::generate(),
            recipientEmail: 'recipient@example.com',
            recipientName: 'Recipient',
            subject: 'Asunto',
            template: 'template',
            provider: 'sendgrid',
            providerMessageId: null,
            status: EmailLogStatus::Sent,
            statusReason: null,
            sentAt: new \DateTimeImmutable('NOW'),
        );

        $this->assertFalse($this->sut->isSubscribedTo($event));
    }
}
