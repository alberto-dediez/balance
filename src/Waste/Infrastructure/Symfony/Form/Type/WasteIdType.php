<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasteIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly WasteRepository $wasteRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $wastes = $this->wasteRepository->findByFilter(
            new WasteFilter(limit: 0),
        );

        $resolver->setDefaults([
            'choices' => $this->getChoices($wastes),
            'class' => WasteId::class,
        ]);
    }
}
