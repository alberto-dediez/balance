<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Infrastructure\Symfony\Twig\CountryExtension;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinationType extends AbstractType
{
    public function __construct(
        private readonly DestinationRepository $destinationRepository,
        private readonly CountryExtension $countryExtension,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $destinations = $this->destinationRepository
            ->findByFilter(
                new DestinationFilter(
                    limit: 0,
                ),
            )
        ;

        $countryExtension = $this->countryExtension;

        $resolver->setDefaults([
            'choices' => $destinations,
            'choice_label' => fn(Destination $destination) => sprintf('%s (%s)', $destination->name(), $countryExtension->countryName($destination->countryId())),
            'class' => Destination::class,
        ]);
    }

    public function getParent()
    {
        return AutocompleteEntityType::class;
    }
}
