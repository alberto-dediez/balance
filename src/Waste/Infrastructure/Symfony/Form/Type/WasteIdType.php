<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Shared\Infrastructure\Symfony\Type\AggregateRootIdType;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasteIdType extends AggregateRootIdType
{
    public function __construct(
        private WasteRepository $wasteRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $wastes = $this->wasteRepository->findByFilter(
            new WasteFilter()
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($wastes),
            'class' => WasteId::class
        ));
    }
}