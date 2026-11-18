<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog\Assistant;

use App\Security\Application\EmailLog\Assistant\ListEmailLogsTool;
use App\Security\Application\EmailLog\EmailLogAuthorizer;
use App\Security\Application\EmailLog\List\ListEmailLogsService;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryEmailLogRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmailLogsToolTest extends TestCase
{
    private InMemoryEmailLogRepository $emailLogs;

    private ListEmailLogsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->emailLogs = EmailLogRepositoryMother::inMemory();
        $authorizer = new EmailLogAuthorizer();
        $this->sut = new ListEmailLogsTool(new ListEmailLogsService($this->emailLogs, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToSuperAdmins(): void
    {
        $this->assertSame('list_email_logs', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::superAdminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsTheEmailsWithoutTheirBody(): void
    {
        $emailLog = EmailLogMother::from(recipientEmail: 'ana@acme.test', subject: 'Bienvenida', template: '@security/users/welcome.email.html.twig', sentAt: new \DateTimeImmutable('2026-11-15 09:30:00'));
        $this->emailLogs->add($emailLog);

        $result = $this->sut->execute($this->arguments([]), UserMother::superAdminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'href' => 'route:admin_emails_show?emailLogId=' . $emailLog->id(),
            'recipient' => 'ana@acme.test',
            'subject' => 'Bienvenida',
            'status' => 'Sent',
            'sentAt' => $this->context()->dateTime(new \DateTimeImmutable('2026-11-15 09:30:00')),
            'template' => '@security/users/welcome.email.html.twig',
        ]], $result->data['emails']);
        $this->assertSame('admin_emails', $result->link?->route);
    }

    public function testFiltersByRecipientStatusAndDates(): void
    {
        $this->emailLogs->add(EmailLogMother::from(recipientEmail: 'ana@acme.test', sentAt: new \DateTimeImmutable('2026-11-10 10:00:00')));
        $this->emailLogs->add(EmailLogMother::from(recipientEmail: 'ana@acme.test', status: EmailLogStatus::Failed, sentAt: new \DateTimeImmutable('2026-11-12 10:00:00')));
        $this->emailLogs->add(EmailLogMother::from(recipientEmail: 'luis@acme.test', sentAt: new \DateTimeImmutable('2026-11-12 23:00:00')));

        $byRecipient = $this->sut->execute($this->arguments(['recipient' => 'ana']), UserMother::superAdminUser(), $this->context());
        $byStatus = $this->sut->execute($this->arguments(['status' => 'Failed']), UserMother::superAdminUser(), $this->context());
        $byDates = $this->sut->execute($this->arguments(['sentFrom' => '2026-11-12', 'sentUntil' => '2026-11-12']), UserMother::superAdminUser(), $this->context());

        $this->assertSame(2, $byRecipient->data['total']);
        $this->assertSame(1, $byStatus->data['total']);
        $this->assertSame(2, $byDates->data['total']);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->emailLogs->add(EmailLogMother::sent());
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), UserMother::superAdminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['emails']);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('UTC'));
    }
}
