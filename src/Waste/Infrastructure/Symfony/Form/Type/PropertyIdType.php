<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class PropertyIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount, 
        private readonly PropertyRepository $propertyRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;

        $account = $loggedAccount();

        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                accountId: null == $account ? null : $account->id(),
                limit: 0
            )
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($properties),
            'class' => PropertyId::class
        ]);
    }
}