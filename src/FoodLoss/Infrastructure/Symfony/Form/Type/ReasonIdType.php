<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Reason\ReasonFilter;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReasonIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly ReasonRepository $reasonRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ReasonId::class,
            'action_priority' => null,
        ]);

        $resolver->setNormalizer('choices', function (Options $options, $value): array {
            $filter = new ReasonFilter(priority: $options['action_priority'] ?? null, limit: 0);

            $reasons = $this->reasonRepository->findByFilter($filter);

            return $this->getChoices($reasons);
        });
    }
}
