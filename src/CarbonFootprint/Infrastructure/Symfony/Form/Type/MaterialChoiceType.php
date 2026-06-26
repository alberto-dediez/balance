<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class MaterialChoiceType extends AbstractType
{
    public function __construct(
        private readonly MaterialRepository $materialRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->materialRepository->findByFilter(new MaterialFilter(limit: 0)) as $material) {
            $choices[$this->label($material, $choices)] = (string) $material->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(Material $material, array $choices): string
    {
        $label = (string) $material;
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
