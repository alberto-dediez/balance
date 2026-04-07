<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyIdsType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                accountId: $account instanceof Account ? $account->id() : null,
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($properties),
            'class' => PropertyId::class,
            'multiple' => true,
            'required' => false,
        ]);
    }
}
