<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriticalPointIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly CriticalPointRepository $repository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $criticalPoints = $this->repository->findByFilter(
            new CriticalPointFilter(
                accountId: $account instanceof Account ? $account->id() : null,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($criticalPoints),
            'class' => CriticalPointId::class,
        ]);
    }
}
