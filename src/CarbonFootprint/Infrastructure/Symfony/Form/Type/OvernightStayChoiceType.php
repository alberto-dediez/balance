<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class OvernightStayChoiceType extends AbstractType
{
    public function __construct(
        private readonly OvernightStayRepository $overnightStayRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->overnightStayRepository->findByFilter(new OvernightStayFilter(limit: 0)) as $overnightStay) {
            $choices[$this->label($overnightStay, $choices)] = (string) $overnightStay->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(OvernightStay $overnightStay, array $choices): string
    {
        $label = (string) $overnightStay;
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
