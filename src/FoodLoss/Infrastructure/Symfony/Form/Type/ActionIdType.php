<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionIdType extends AggregateRootIdType
{
    public function __construct(
        private ActionRepository $actionRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $actions = $this->actionRepository->findByFilter(
            new ActionFilter(limit: 0),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($actions),
            'class' => ActionId::class,
        ]);
    }
}
