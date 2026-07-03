<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @extends AbstractType<mixed> */
class MachineryFuelChoiceType extends AbstractType
{
    public function __construct(
        private readonly MachineryFuelRepository $machineryFuelRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->machineryFuelRepository->findByFilter(new MachineryFuelFilter(limit: 0)) as $machineryFuel) {
            $choices[$this->label($machineryFuel, $choices)] = (string) $machineryFuel->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(MachineryFuel $machineryFuel, array $choices): string
    {
        $label = sprintf(
            '%s - %s',
            $machineryFuel->name(),
            $this->translator->trans($machineryFuel->machineryType()->value, [], 'machineryFuelMachineryType'),
        );

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
