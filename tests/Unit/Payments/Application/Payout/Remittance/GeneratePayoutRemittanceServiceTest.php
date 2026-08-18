<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Remittance;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Application\Payout\Remittance\EmptyRemittanceException;
use App\Payments\Application\Payout\Remittance\GeneratePayoutRemittanceRequest;
use App\Payments\Application\Payout\Remittance\GeneratePayoutRemittanceService;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Payments\Infrastructure\Remittance\CsvRemittanceGenerator;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GeneratePayoutRemittanceServiceTest extends TestCase
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

    public function testItGeneratesALineForEachDraftPayout(): void
    {
        $this->draftPayoutFor($this->readyPayee());
        $this->draftPayoutFor($this->readyPayee());

        $response = $this->service()(
            new GeneratePayoutRemittanceRequest(format: 'csv'),
            UserMother::adminUser(),
        );

        $this->assertSame(2, $response->linesCount);
        $this->assertSame(560.0, $response->totalAmount);
        $this->assertStringContainsString('ES9121000418450200051332', $response->content);
    }

    public function testGeneratingTheRemittanceDoesNotChangeTheStatus(): void
    {
        $payout = $this->draftPayoutFor($this->readyPayee());

        $this->service()(new GeneratePayoutRemittanceRequest(format: 'csv'), UserMother::adminUser());

        $stored = $this->payouts->find($payout->id());

        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Draft, $stored->status());
    }

    public function testItRejectsAnEmptyRemittance(): void
    {
        $this->expectException(EmptyRemittanceException::class);

        $this->service()(new GeneratePayoutRemittanceRequest(format: 'csv'), UserMother::adminUser());
    }

    public function testAnOwnerCannotGenerateARemittance(): void
    {
        $this->draftPayoutFor($this->readyPayee());

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new GeneratePayoutRemittanceRequest(format: 'csv'),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function readyPayee(): Payee
    {
        $payee = PayeeMother::ready();

        $this->payees->add($payee);

        return $payee;
    }

    private function draftPayoutFor(Payee $payee): Payout
    {
        $payout = PayoutMother::draft($payee->id());

        $this->payouts->add($payout);

        return $payout;
    }

    private function service(): GeneratePayoutRemittanceService
    {
        return new GeneratePayoutRemittanceService(
            $this->payouts,
            $this->payees,
            new PayoutAuthorizer(),
            [new CsvRemittanceGenerator()],
        );
    }
}
