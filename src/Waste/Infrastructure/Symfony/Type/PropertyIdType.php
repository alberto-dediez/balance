<?php

namespace App\Waste\Infrastructure\Symfony\Type;

use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Type\AggregateRootIdType;

class PropertyIdType extends AggregateRootIdType
{
    public function __construct(
        private LoggedAccount $loggedAccount, 
        private PropertyRepository $propertyRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $loggedAccount = $this->loggedAccount;

        $account = $loggedAccount();

        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                accountId: null == $account ? null : $account->id()
            )
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($properties),
            'class' => PropertyId::class
        ));
    }
}