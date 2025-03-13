<?php

namespace App\Security\Infrastructure\Symfony\Type;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Infrastructure\Symfony\Type\AggregateRootIdType;

class AccountIdType extends AggregateRootIdType
{
    public function __construct(private AccountRepository $accountRepository)
    {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $accounts = $this->accountRepository->findByFilter(new AccountFilter());

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($accounts),
            'class' => AccountId::class
        ));
    }
}