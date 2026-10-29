<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountIdType extends AggregateRootIdType
{
    public function __construct(private readonly AccountRepository $accountRepository) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => AccountId::class,
            'excluded_account_id' => null,
        ]);

        $resolver->setAllowedTypes('excluded_account_id', ['null', AccountId::class]);

        $resolver->setNormalizer('choices', function ($options) {
            $excludedAccountId = $options['excluded_account_id'];
            $accounts = $this->accountRepository->findByFilter(new AccountFilter(limit: 0));

            if ($excludedAccountId instanceof AccountId) {
                $accounts = array_values(array_filter(
                    $accounts,
                    static fn(Account $account): bool => !$account->id()->equals($excludedAccountId),
                ));
            }

            return $this->getChoices($accounts);
        });
    }
}
