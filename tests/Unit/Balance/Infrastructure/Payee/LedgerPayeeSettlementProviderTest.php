<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Payee;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Infrastructure\Payee\LedgerPayeeSettlementProvider;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\UI\Web\Admin\Payee\PayeeSettlement;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LedgerPayeeSettlementProviderTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryLedgerEntryRepository $ledger;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItOffersToSettleAReadyPayeeWithAPendingBalance(): void
    {
        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 30.0));

        $payee = PayeeMother::ready((string) $this->accountId);

        $settlement = $this->settlementsOf($payee)[(string) $payee->id()];

        $this->assertSame(30.0, $settlement->pendingAmount);
        $this->assertTrue($settlement->isSettleable());
        $this->assertStringContainsString((string) $this->accountId, (string) $settlement->settlePath);
    }

    public function testItReportsTheBalanceButDoesNotOfferToSettleAnIncompletePayee(): void
    {
        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 30.0));

        $payee = PayeeMother::incomplete((string) $this->accountId);

        $settlement = $this->settlementsOf($payee)[(string) $payee->id()];

        $this->assertSame(30.0, $settlement->pendingAmount);
        $this->assertFalse($settlement->isSettleable());
    }

    public function testItDoesNotOfferToSettleAPayeeWithoutPendingBalance(): void
    {
        $payee = PayeeMother::ready((string) $this->accountId);

        $settlement = $this->settlementsOf($payee)[(string) $payee->id()];

        $this->assertSame(0.0, $settlement->pendingAmount);
        $this->assertFalse($settlement->isSettleable());
    }

    public function testItIgnoresTheBalanceOfOtherBeneficiaries(): void
    {
        $this->ledger->add(LedgerEntryMother::forAccount(AccountId::generate(), 30.0));

        $payee = PayeeMother::ready((string) $this->accountId);

        $this->assertSame(0.0, $this->settlementsOf($payee)[(string) $payee->id()]->pendingAmount);
    }

    public function testThePayeeTypeAndTheLedgerBeneficiaryShareTheirValues(): void
    {
        $this->assertSame(LedgerBeneficiary::Account->value, PayeeType::Account->value);
        $this->assertSame(LedgerBeneficiary::Project->value, PayeeType::Project->value);
    }

    /** @return array<string, PayeeSettlement> */
    private function settlementsOf(Payee ...$payees): array
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn(string $route, array $parameters): string => $route . '?' . http_build_query($parameters),
        );

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('getToken')->willReturnCallback(
            static fn(string $tokenId): CsrfToken => new CsrfToken($tokenId, 'token'),
        );

        return (new LedgerPayeeSettlementProvider($this->ledger, $router, $csrfTokenManager))->settlementsOf($payees);
    }
}
