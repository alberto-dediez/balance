<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

final class OwnerContributorDefaults
{
    public function __construct(
        private PayeeRepository $payeeRepository,
        private AccountRepository $accountRepository,
    ) {}

    public function __invoke(AccountId $accountId, User $user): OwnerContributorDefaultsResponse
    {
        $payee = $this->payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId);
        $taxId = $payee?->taxId();

        return new OwnerContributorDefaultsResponse(
            name: $this->name($payee, $accountId),
            email: $user->username(),
            country: $payee?->address()->country(),
            isCompany: $payee instanceof Payee && null !== $taxId && '' !== $taxId,
            companyName: $payee?->legalName(),
            vatNumber: $taxId,
        );
    }

    private function name(?Payee $payee, AccountId $accountId): string
    {
        $legalName = $payee?->legalName();

        if (null !== $legalName && '' !== $legalName) {
            return $legalName;
        }

        return $this->accountRepository->find($accountId)?->name() ?? '';
    }
}
