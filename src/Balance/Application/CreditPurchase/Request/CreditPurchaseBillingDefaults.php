<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseBilling;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

final class CreditPurchaseBillingDefaults
{
    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private PayeeRepository $payeeRepository,
        private AccountRepository $accountRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(AccountId $accountId, User $user): CreditPurchaseBillingDefaultsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: CreditPurchaseId::generate(), accountId: $accountId),
            SecurityAction::Register,
            $user,
        );

        $billing = $this->creditPurchaseRepository->latestWithBillingFor($accountId)?->billing();

        if ($billing instanceof CreditPurchaseBilling) {
            return new CreditPurchaseBillingDefaultsResponse(
                legalName: $billing->legalName(),
                taxId: $billing->taxId(),
                street: $billing->street(),
                postalCode: $billing->postalCode(),
                city: $billing->city(),
                country: $billing->country(),
                email: $billing->email(),
            );
        }

        $payee = $this->payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId);

        if ($payee instanceof Payee) {
            $address = $payee->address();

            return new CreditPurchaseBillingDefaultsResponse(
                legalName: '' !== $payee->legalName() ? $payee->legalName() : $this->accountName($accountId),
                taxId: $payee->taxId() ?? '',
                street: $address->street() ?? '',
                postalCode: $address->postalCode() ?? '',
                city: $address->city() ?? '',
                country: strtoupper(trim($address->country() ?? '')),
                email: $user->username(),
            );
        }

        return new CreditPurchaseBillingDefaultsResponse(
            legalName: $this->accountName($accountId),
            taxId: '',
            street: '',
            postalCode: '',
            city: '',
            country: '',
            email: $user->username(),
        );
    }

    private function accountName(AccountId $accountId): string
    {
        return $this->accountRepository->find($accountId)?->name() ?? '';
    }
}
