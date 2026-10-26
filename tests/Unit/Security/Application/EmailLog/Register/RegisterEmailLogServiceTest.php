<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog\Register;

use App\Security\Application\EmailLog\Register\RegisterEmailLogRequest;
use App\Security\Application\EmailLog\Register\RegisterEmailLogService;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRegistered;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterEmailLogServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItRegistersSentEmailLogWithReceivedId(): void
    {
        $repository = EmailLogRepositoryMother::inMemory();
        $id = EmailLogId::generate();

        $sut = new RegisterEmailLogService($repository);

        $response = $sut($this->request((string) $id, EmailLogStatus::Sent, 'message-id', null));

        $this->assertTrue($id->equals($response->emailLogId));

        $emailLog = $repository->find($id);
        $this->assertNotNull($emailLog);
        $this->assertEquals(EmailLogStatus::Sent, $emailLog->status());
        $this->assertEquals('message-id', $emailLog->providerMessageId());
        $this->assertEquals('recipient@example.com', $emailLog->recipientEmail());
        $this->assertInstanceOf(EmailLogRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testItRegistersFailedEmailLogWithReason(): void
    {
        $repository = EmailLogRepositoryMother::inMemory();
        $id = EmailLogId::generate();

        $sut = new RegisterEmailLogService($repository);

        $sut($this->request((string) $id, EmailLogStatus::Failed, null, 'transport down'));

        $emailLog = $repository->find($id);
        $this->assertNotNull($emailLog);
        $this->assertEquals(EmailLogStatus::Failed, $emailLog->status());
        $this->assertEquals('transport down', $emailLog->statusReason());
        $this->assertNull($emailLog->providerMessageId());
    }

    private function request(string $id, EmailLogStatus $status, ?string $providerMessageId, ?string $reason): RegisterEmailLogRequest
    {
        return new RegisterEmailLogRequest(
            emailLogId: $id,
            recipientEmail: 'recipient@example.com',
            recipientName: 'Recipient',
            subject: 'Asunto',
            template: '@shared/layout/email.html.twig',
            provider: 'sendgrid',
            providerMessageId: $providerMessageId,
            status: $status,
            statusReason: $reason,
            sentAt: new \DateTimeImmutable('NOW'),
        );
    }
}
