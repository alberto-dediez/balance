<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class EconomicSectorChoiceType extends AbstractType
{
    public function __construct(
        private readonly EconomicSectorRepository $economicSectorRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->economicSectorRepository->findByFilter(new EconomicSectorFilter(limit: 0)) as $economicSector) {
            $choices[$this->label($economicSector, $choices)] = (string) $economicSector->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(EconomicSector $economicSector, array $choices): string
    {
        $label = null !== $economicSector->classificationCode()
            ? sprintf('%s · %s', $economicSector->classificationCode(), $economicSector)
            : (string) $economicSector;
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
