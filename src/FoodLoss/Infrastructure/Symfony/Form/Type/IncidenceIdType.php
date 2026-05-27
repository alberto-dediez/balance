<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncidenceIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly IncidenceRepository $incidenceRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $incidences = $this->incidenceRepository->findByFilter(
            new IncidenceFilter(
                accountId: $account instanceof Account ? $account->id() : null,
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($incidences),
            'class' => IncidenceId::class,
        ]);
    }
}
