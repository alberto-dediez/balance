<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Statement;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Application\Payout\Statement\DownloadPayoutStatementRequest;
use App\Payments\Application\Payout\Statement\DownloadPayoutStatementService;
use App\Payments\Application\Payout\View\ViewPayoutService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutStatementLinesProvider;
use App\Payments\Domain\Model\Payout\PayoutStatementPdfGenerator;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadPayoutStatementServiceTest extends TestCase
{
    private InMemoryPayoutRepository $payouts;

    private InMemoryPayeeRepository $payees;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payouts = PayoutRepositoryMother::inMemory();
        $this->payees = PayeeRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItGeneratesAStatementForAPayout(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $response = $this->service()(
            new DownloadPayoutStatementRequest($payout->id()),
            UserMother::adminUser(),
        );

        $this->assertSame('/tmp/statement.pdf', $response->path);
        $this->assertSame('liquidacion-2026-0001.pdf', $response->filename);
    }

    public function testItFailsWhenPayeeIsNotFound(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new DownloadPayoutStatementRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    private function service(): DownloadPayoutStatementService
    {
        $viewPayout = new ViewPayoutService(
            repository: $this->payouts,
            payeeRepository: $this->payees,
            authorizer: new PayoutAuthorizer(),
        );

        return new DownloadPayoutStatementService(
            viewPayout: $viewPayout,
            linesProvider: $this->linesProvider(),
            pdfGenerator: $this->pdfGenerator(),
        );
    }

    private function linesProvider(): PayoutStatementLinesProvider
    {
        return new class implements PayoutStatementLinesProvider {
            public function linesOf(PayoutId $payoutId): array
            {
                return [];
            }
        };
    }

    private function pdfGenerator(): PayoutStatementPdfGenerator
    {
        return new class implements PayoutStatementPdfGenerator {
            public function generate(Payout $payout, Payee $payee, array $lines): string
            {
                return '/tmp/statement.pdf';
            }
        };
    }
}
