<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

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
        $accounts = $this->accountRepository->findByFilter(new AccountFilter(limit: 0));

        $resolver->setDefaults([
            'choices' => $this->getChoices($accounts),
            'class' => AccountId::class,
        ]);
    }
}
