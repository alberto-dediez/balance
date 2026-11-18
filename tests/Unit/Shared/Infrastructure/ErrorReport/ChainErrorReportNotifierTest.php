<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportContext;
use App\Shared\Domain\Model\ErrorReport\ErrorReportException;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\ChainErrorReportNotifier;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Event\SpyErrorReportNotifier;
use PHPUnit\Framework\TestCase;

class ChainErrorReportNotifierTest extends TestCase
{
    private SpyLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new SpyLogger();
    }

    public function testItInvokesEveryNotifierWithTheSameReport(): void
    {
        $first = new SpyErrorReportNotifier();
        $second = new SpyErrorReportNotifier();
        $report = $this->report();

        $sut = $this->createSUT(notifiers: [$first, $second]);

        $sut($report);

        $this->assertSame($report, $first->lastReport());
        $this->assertSame($report, $second->lastReport());
        $this->assertSame([], $this->logger->levels());
    }

    public function testItKeepsNotifyingWhenANotifierFails(): void
    {
        $second = new SpyErrorReportNotifier();
        $report = $this->report();

        $sut = $this->createSUT(notifiers: [new ThrowingErrorReportNotifier(), $second]);

        $sut($report);

        $this->assertSame($report, $second->lastReport());
        $this->assertSame(['error'], $this->logger->levels());
    }

    public function testItDoesNotFailWithoutNotifiers(): void
    {
        $sut = $this->createSUT(notifiers: []);

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

    /**
     * @param ErrorReportNotifier[] $notifiers
     */
    private function createSUT(array $notifiers): ChainErrorReportNotifier
    {
        return new ChainErrorReportNotifier(
            notifiers: $notifiers,
            logger: $this->logger,
        );
    }
}
