<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportRequest;
use App\Shared\Infrastructure\ErrorReport\EmailErrorReportNotifier;
use App\Shared\Infrastructure\Symfony\Event\EmailListener;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Email;

class EmailErrorReportNotifierIntegrationTest extends KernelTestCase
{
    public function testItSendsTheErrorReportEmail(): void
    {
        self::bootKernel();

        $emailListener = self::getContainer()->get(EmailListener::class);

        $this->assertInstanceOf(EmailListener::class, $emailListener);

        $emailListener->reset();

        $sut = new EmailErrorReportNotifier(
            emailSender: $this->emailSender(),
            throttle: new StubErrorReportThrottle(),
            logger: new NullLogger(),
            enabled: true,
            recipients: 'dev@email.com',
        );

        $sut($this->report());

        $messages = $emailListener->getMessages();

        $this->assertCount(1, $messages);

        $message = $messages[0];

        $this->assertInstanceOf(Email::class, $message);
        $this->assertSame('dev@email.com', $message->getTo()[0]->getAddress());
        $this->assertSame('[prod] Error 500 - RuntimeException: boom (#ABCD1234)', $message->getSubject());

        $body = (string) $message->getHtmlBody();

        $this->assertStringContainsString('ABCD1234', $body);
        $this->assertStringContainsString('https://localhost/es/waste', $body);
        $this->assertStringContainsString('admin@email.com', $body);
        $this->assertStringContainsString('impersonada', $body);
        $this->assertStringContainsString('#0 /app/src/Foo.php(12)', $body);
        $this->assertStringNotContainsString('a-real-password', $body);
    }

    private function report(): ErrorReport
    {
        return new ErrorReport(
            incidentId: 'ABCD1234',
            fingerprint: 'a-fingerprint',
            statusCode: 500,
            exception: new ErrorReportException(
                class: \RuntimeException::class,
                message: 'boom',
                file: '/app/src/Foo.php',
                line: 12,
                trace: '#0 /app/src/Foo.php(12): foo()',
            ),
            context: new ErrorReportContext(
                userId: 'a-user-id',
                username: 'admin@email.com',
                roles: ['ROLE_OWNER'],
                impersonated: true,
                impersonatorUsername: 'super@email.com',
                currentAccountName: 'impersonada',
            ),
            request: new ErrorReportRequest(
                uri: 'https://localhost/es/waste',
                method: 'POST',
                body: ['password' => '***'],
            ),
            environment: 'prod',
        );
    }

    private function emailSender(): EmailSender
    {
        $emailSender = self::getContainer()->get(EmailSender::class);

        $this->assertInstanceOf(EmailSender::class, $emailSender);

        return $emailSender;
    }
}
