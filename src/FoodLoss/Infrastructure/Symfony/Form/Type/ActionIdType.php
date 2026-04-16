<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ActionId::class,
            'action_priority' => null,
        ]);

        $resolver->setNormalizer('choices', function (Options $options, $value): array {
            $priority = $options['action_priority'] ?? null;
            $filter = new ActionFilter(priority: $priority, limit: 0);

            $actions = $this->actionRepository->findByFilter($filter);

            if (null !== $priority) {
                return $this->getChoices($actions);
            }

            return $this->getChoicesGroupedByPriority($actions);
        });
    }

    /**
     * @param array<int, Action> $actions
     *
     * @return array<string, array<string, string>>
     */
    private function getChoicesGroupedByPriority(array $actions): array
    {
        $grouped = [];

        foreach (Priority::cases() as $priority) {
            $grouped[$priority->value] = [];
        }

        foreach ($actions as $action) {
            $group = $action->priority()->value;
            $name = (string) $action;
            $i = 0;

            while (isset($grouped[$group][$name])) {
                $name = sprintf('%s (%d)', (string) $action, ++$i);
            }

            $grouped[$group][$name] = (string) $action->id();
        }

        return array_filter($grouped, static fn(array $choices): bool => [] !== $choices);
    }
}
