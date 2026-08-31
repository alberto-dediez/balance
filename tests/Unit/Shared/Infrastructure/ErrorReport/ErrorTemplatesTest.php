<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportRequest;
use App\Shared\Infrastructure\ErrorReport\IncidentIdHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class ErrorTemplatesTest extends KernelTestCase
{
    /**
     * @return array<string,array{string,int}>
     */
    public static function templates(): array
    {
        return [
            'error' => ['bundles/TwigBundle/Exception/error.html.twig', 500],
            'error 500' => ['bundles/TwigBundle/Exception/error500.html.twig', 500],
            'error 404' => ['bundles/TwigBundle/Exception/error404.html.twig', 404],
            'error 403' => ['bundles/TwigBundle/Exception/error403.html.twig', 403],
            'error 503' => ['bundles/TwigBundle/Exception/error503.html.twig', 503],
        ];
    }

    /**
     * @dataProvider templates
     */
    public function testItRendersTheErrorPage(string $template, int $statusCode): void
    {
        self::bootKernel();

        $content = $this->twig()->render($template, ['status_code' => $statusCode, 'status_text' => 'error']);

        $this->assertStringContainsString((string) $statusCode, $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testItRendersTheIncidentIdInTheServerErrorPage(): void
    {
        self::bootKernel();

        $this->incidentIdHolder()->set('ABCD1234');

        $content = $this->twig()->render('bundles/TwigBundle/Exception/error500.html.twig', ['status_code' => 500]);

        $this->assertStringContainsString('ABCD1234', $content);
    }

    public function testItRendersTheErrorReportEmail(): void
    {
        self::bootKernel();

        $content = $this->twig()->render('@shared/mail/error_report.html.twig', ['report' => $this->report()]);

        $this->assertStringContainsString('ABCD1234', $content);
        $this->assertStringContainsString('RuntimeException', $content);
        $this->assertStringContainsString('https://localhost/es/waste', $content);
        $this->assertStringContainsString('admin@email.com', $content);
        $this->assertStringContainsString('impersonada', $content);
        $this->assertStringContainsString('#0 /app/src/Foo.php(12)', $content);
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
                impersonatorId: 'an-impersonator-id',
                impersonatorUsername: 'super@email.com',
                currentAccountId: 'an-account-id',
                currentAccountName: 'impersonada',
                ownerAccountId: 'another-account-id',
            ),
            request: new ErrorReportRequest(
                uri: 'https://localhost/es/waste',
                method: 'POST',
                route: 'waste_list',
                clientIp: '127.0.0.1',
                locale: 'es',
                query: ['page' => '2'],
                body: ['password' => '***'],
                headers: ['user-agent' => 'a browser'],
            ),
            environment: 'prod',
        );
    }

    private function twig(): Environment
    {
        $twig = self::getContainer()->get('twig');

        $this->assertInstanceOf(Environment::class, $twig);

        return $twig;
    }

    private function incidentIdHolder(): IncidentIdHolder
    {
        $holder = self::getContainer()->get(IncidentIdHolder::class);

        $this->assertInstanceOf(IncidentIdHolder::class, $holder);

        return $holder;
    }
}
