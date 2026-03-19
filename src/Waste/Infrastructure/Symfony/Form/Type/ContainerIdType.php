<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContainerIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly ContainerRepository $containerRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;

        $account = $loggedAccount();

        $containers = $this->containerRepository->findByFilter(
            new ContainerFilter(
                accountId: null == $account ? null : $account->id(),
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($containers),
            'class' => ContainerId::class,
        ]);
    }
}
