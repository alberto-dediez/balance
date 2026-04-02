<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionIdType extends AggregateRootIdType
{
    public function __construct(
        private ActionRepository $actionRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ActionId::class,
            'action_priority' => null,
        ]);

        $resolver->setNormalizer('choices', function (Options $options, $value) {
            $filter = new ActionFilter(priority: $options['action_priority'] ?? null, limit: 0);

            $actions = $this->actionRepository->findByFilter($filter);

            return $this->getChoices($actions);
        });
    }
}
