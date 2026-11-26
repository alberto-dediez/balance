<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Request\CreditPurchaseBillingDefaults;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Model\Address;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseBillingMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseBillingDefaultsTest extends TestCase
{
    private InMemoryCreditPurchaseRepository $creditPurchaseRepository;

    private InMemoryPayeeRepository $payeeRepository;

    private InMemoryAccountRepository $accountRepository;

    protected function setUp(): void
    {
        $this->creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $this->payeeRepository = PayeeRepositoryMother::inMemory();
        $this->accountRepository = AccountRepositoryMother::inMemory();
    }

    public function testUsesThePreviousRequestEvenWithAPayee(): void
    {
        $accountId = AccountId::generate();
        $this->creditPurchaseRepository->add(CreditPurchaseMother::pendingWithBilling(
            $accountId,
            billing: CreditPurchaseBillingMother::from(legalName: 'Anterior SL', country: 'FRA', email: 'anterior@example.com'),
        ));
        $this->payeeRepository->add(PayeeMother::from(subjectId: (string) $accountId, legalName: 'Beneficiario SL'));

        $defaults = $this->createSUT()($accountId, UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $accountId));

        $this->assertSame('Anterior SL', $defaults->legalName);
        $this->assertSame('B12345678', $defaults->taxId);
        $this->assertSame('Calle Mayor 1', $defaults->street);
        $this->assertSame('28001', $defaults->postalCode);
        $this->assertSame('Madrid', $defaults->city);
        $this->assertSame('FRA', $defaults->country);
        $this->assertSame('anterior@example.com', $defaults->email);
    }

    public function testUsesThePayeeWithTheUserEmailWithoutAPreviousRequest(): void
    {
        $accountId = AccountId::generate();
        $this->creditPurchaseRepository->add(CreditPurchaseMother::pending($accountId));
        $this->payeeRepository->add(PayeeMother::from(subjectId: (string) $accountId, legalName: 'Beneficiario SL', taxId: 'B87654321'));

        $defaults = $this->createSUT()($accountId, UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $accountId));

        $this->assertSame('Beneficiario SL', $defaults->legalName);
        $this->assertSame('B87654321', $defaults->taxId);
        $this->assertSame('Calle Mayor 1', $defaults->street);
        $this->assertSame('28001', $defaults->postalCode);
        $this->assertSame('Madrid', $defaults->city);
        $this->assertSame('ES', $defaults->country);
        $this->assertSame('owner@example.com', $defaults->email);
    }

    public function testAPayeeWithNullFieldsGivesEmptyStrings(): void
    {
        $accountId = AccountId::generate();
        $payee = new Payee(id: PayeeId::generate());
        $payee->register(
            type: PayeeType::Account,
            subjectId: (string) $accountId,
            legalName: 'Beneficiario SL',
            taxId: null,
            address: new Address(),
            iban: null,
            bic: null,
            holderName: null,
            notes: null,
        );
        $this->payeeRepository->add($payee);

        $defaults = $this->createSUT()($accountId, UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $accountId));

        $this->assertSame('Beneficiario SL', $defaults->legalName);
        $this->assertSame('', $defaults->taxId);
        $this->assertSame('', $defaults->street);
        $this->assertSame('', $defaults->postalCode);
        $this->assertSame('', $defaults->city);
        $this->assertSame('', $defaults->country);
        $this->assertSame('owner@example.com', $defaults->email);
    }

    public function testUsesTheAccountNameAndTheUserEmailWithoutAnyData(): void
    {
        $account = AccountMother::fromName('Mi Cuenta');
        $this->accountRepository->add($account);

        $defaults = $this->createSUT()($account->id(), UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $account->id()));

        $this->assertSame('Mi Cuenta', $defaults->legalName);
        $this->assertSame('', $defaults->taxId);
        $this->assertSame('', $defaults->street);
        $this->assertSame('', $defaults->postalCode);
        $this->assertSame('', $defaults->city);
        $this->assertSame('', $defaults->country);
        $this->assertSame('owner@example.com', $defaults->email);
    }

    public function testAUserOfAnotherAccountIsNotAuthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(AccountId::generate(), UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, AccountId::generate()));
    }

    public function testAUserRoleIsNotAuthorized(): void
    {
        $accountId = AccountId::generate();

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($accountId, UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $accountId));
    }

    private function createSUT(): CreditPurchaseBillingDefaults
    {
        return new CreditPurchaseBillingDefaults(
            creditPurchaseRepository: $this->creditPurchaseRepository,
            payeeRepository: $this->payeeRepository,
            accountRepository: $this->accountRepository,
            authorizer: new CreditPurchaseAuthorizer(),
        );
    }
}
