<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ElectricityMarketerChoiceType extends AbstractType
{
    public function __construct(
        private readonly ElectricityMarketerRepository $electricityMarketerRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->electricityMarketerRepository->findByFilter(new ElectricityMarketerFilter(limit: 0)) as $electricityMarketer) {
            $choices[$this->label($electricityMarketer, $choices)] = (string) $electricityMarketer->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(ElectricityMarketer $electricityMarketer, array $choices): string
    {
        $label = (string) $electricityMarketer;
        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
