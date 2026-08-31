<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Model\ErrorReport\ErrorReport;
use App\Shared\Domain\Model\ErrorReport\ErrorReportNotifier;
use App\Shared\Infrastructure\ErrorReport\ErrorReportFactory;
use App\Shared\Infrastructure\ErrorReport\IncidentIdHolder;
use App\Shared\Infrastructure\Symfony\Event\ExceptionListener;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\StubLoggedUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends TestCase
{
    private SpyErrorReportNotifier $notifier;

    private IncidentIdHolder $incidentIdHolder;

    protected function setUp(): void
    {
        $this->notifier = new SpyErrorReportNotifier();
        $this->incidentIdHolder = new IncidentIdHolder();
    }

    public function testItNotifiesServerErrors(): void
    {
        $sut = $this->createSUT();

        $sut($this->event(new \RuntimeException('boom')));

        $report = $this->notifier->lastReport();

        $this->assertInstanceOf(ErrorReport::class, $report);
        $this->assertSame(500, $report->statusCode);
        $this->assertSame($report->incidentId, $this->incidentIdHolder->get());
    }

    public function testItDoesNotNotifyClientErrors(): void
    {
        $sut = $this->createSUT();

        $sut($this->event(new NotFoundHttpException()));
        $sut($this->event(new AccessDeniedHttpException()));

        $this->assertNull($this->notifier->lastReport());
        $this->assertNotNull($this->incidentIdHolder->get());
    }

    private function event(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/es/waste'),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }

    private function createSUT(): ExceptionListener
    {
        return new ExceptionListener(
            new ErrorReportFactory(
                loggedUser: new StubLoggedUser(),
                currentAccountIdProvider: new StubCurrentAccountIdProvider(),
                currentRolesProvider: new StubCurrentRolesProvider(),
                accountRepository: AccountRepositoryMother::inMemory(),
                environment: 'test',
            ),
            $this->notifier,
            $this->incidentIdHolder,
        );
    }
}

class SpyErrorReportNotifier implements ErrorReportNotifier
{
    private ?ErrorReport $report = null;

    public function __invoke(ErrorReport $report): void
    {
        $this->report = $report;
    }

    public function lastReport(): ?ErrorReport
    {
        return $this->report;
    }
}
