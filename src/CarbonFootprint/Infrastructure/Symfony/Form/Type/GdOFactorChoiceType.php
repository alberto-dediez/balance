<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class GdOFactorChoiceType extends AbstractType
{
    public function __construct(
        private readonly GdOFactorRepository $gdOFactorRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->gdOFactorRepository->findByFilter(new GdOFactorFilter(limit: 0)) as $gdOFactor) {
            $choices[$this->label($gdOFactor, $choices)] = (string) $gdOFactor->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(GdOFactor $gdOFactor, array $choices): string
    {
        $label = (string) $gdOFactor;
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
