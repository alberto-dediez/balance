<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\Property\Domain\Model\Destination\Destination;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Infrastructure\Symfony\Twig\CountryExtension;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteEntityType;

class DestinationType extends AbstractType
{
    public function __construct(
        private readonly DestinationRepository $destinationRepository,
        private readonly CountryExtension $countryExtension
    ) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $destinations = $this->destinationRepository
            ->findByFilter(
                new DestinationFilter(
                    limit: 0
                )
            )
        ;

        $countryExtension = $this->countryExtension;

        $resolver->setDefaults([
            'choices' => $destinations,
            'choice_label' => fn(Destination $destination) => sprintf("%s (%s)", $destination->name(), $countryExtension->countryName($destination->countryId())),
            'class' => Destination::class
        ]);
    }
    
    public function getParent()
    {
        return AutocompleteEntityType::class;
    }
}