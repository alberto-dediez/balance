<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class ContainerIdType extends AggregateRootIdType
{
    public function __construct(
        private LoggedAccount $loggedAccount, 
        private ContainerRepository $containerRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $loggedAccount = $this->loggedAccount;

        $account = $loggedAccount();

        $containers = $this->containerRepository->findByFilter(
            new ContainerFilter(
                accountId: null == $account ? null : $account->id(),
                limit: 0
            )
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($containers),
            'class' => ContainerId::class
        ));
    }
}