<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\ChainErrorReportNotifier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ErrorReportNotifierWiringTest extends KernelTestCase
{
    public function testTheErrorReportNotifierIsTheChainOfChannels(): void
    {
        self::bootKernel();

        $sut = self::getContainer()->get(ErrorReportNotifier::class);

        $this->assertInstanceOf(ChainErrorReportNotifier::class, $sut);
        $this->assertFalse(self::getContainer()->getParameter('error_report.erp.enabled'));

        $sut($this->report());
    }

    private function report(): ErrorReport
    {
        return new ErrorReport(
            incidentId: 'ABCD1234',
            fingerprint: sha1(uniqid('wiring', true)),
            statusCode: 500,
            exception: new ErrorReportException(\RuntimeException::class, 'boom', '/app/src/Foo.php', 12),
            context: new ErrorReportContext(),
            environment: 'test',
        );
    }
}
