<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerFilter;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasteManagerIdType extends AggregateRootIdType
{
    public function __construct(
        private WasteManagerRepository $wasteRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $wastes = $this->wasteRepository->findByFilter(
            new WasteManagerFilter(limit: 0)
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($wastes),
            'class' => WasteManagerId::class
        ));
    }
}