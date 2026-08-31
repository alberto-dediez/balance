<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;
use App\Shared\Infrastructure\ErrorReport\EmailErrorReportNotifier;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class EmailErrorReportNotifierTest extends TestCase
{
    public function testItSendsAnEmailToEveryRecipient(): void
    {
        $emailSender = new SpyEmailSender();

        $sut = $this->createSUT($emailSender, recipients: 'dev@email.com, ops@email.com');

        $sut($this->report());

        $sent = $emailSender->sent();

        $this->assertCount(2, $sent);
        $this->assertSame('dev@email.com', $sent[0]['recipient']->email);
        $this->assertSame('ops@email.com', $sent[1]['recipient']->email);
        $this->assertSame('@shared/mail/error_report.html.twig', $sent[0]['template']);
        $this->assertSame('[prod] Error 500 - RuntimeException: boom (#ABCD1234)', $sent[0]['subject']);
    }

    public function testItDoesNotSendAnythingWhenItIsDisabled(): void
    {
        $emailSender = new SpyEmailSender();

        $sut = $this->createSUT($emailSender, enabled: false);

        $sut($this->report());

        $this->assertCount(0, $emailSender->sent());
    }

    public function testItDoesNotSendAnythingWithoutRecipients(): void
    {
        $emailSender = new SpyEmailSender();

        $sut = $this->createSUT($emailSender, recipients: '');

        $sut($this->report());

        $this->assertCount(0, $emailSender->sent());
    }

    public function testItDoesNotSendAnythingWhenTheErrorIsThrottled(): void
    {
        $emailSender = new SpyEmailSender();

        $sut = $this->createSUT($emailSender, throttle: new StubErrorReportThrottle(false));

        $sut($this->report());

        $this->assertCount(0, $emailSender->sent());
    }

    public function testItDoesNotPropagateEmailFailures(): void
    {
        $sut = $this->createSUT(new SpyEmailSender(shouldFail: true));

        $sut($this->report());

        $this->expectNotToPerformAssertions();
    }

    private function report(): ErrorReport
    {
        return new ErrorReport(
            incidentId: 'ABCD1234',
            fingerprint: 'a-fingerprint',
            statusCode: 500,
            exception: new ErrorReportException(\RuntimeException::class, 'boom', '/app/src/Foo.php', 12),
            context: new ErrorReportContext(),
            environment: 'prod',
        );
    }

    private function createSUT(
        EmailSender $emailSender,
        ?ErrorReportThrottle $throttle = null,
        bool $enabled = true,
        string $recipients = 'dev@email.com',
    ): EmailErrorReportNotifier {
        return new EmailErrorReportNotifier(
            emailSender: $emailSender,
            throttle: $throttle ?? new StubErrorReportThrottle(),
            logger: new NullLogger(),
            enabled: $enabled,
            recipients: $recipients,
        );
    }
}
