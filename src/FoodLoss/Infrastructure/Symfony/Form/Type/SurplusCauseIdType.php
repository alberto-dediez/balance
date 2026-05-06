<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseFilter;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurplusCauseIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly SurplusCauseRepository $surplusCauseRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $surplusCauses = $this->surplusCauseRepository->findByFilter(
            new SurplusCauseFilter(limit: 0),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($surplusCauses),
            'class' => SurplusCauseId::class,
        ]);
    }
}
