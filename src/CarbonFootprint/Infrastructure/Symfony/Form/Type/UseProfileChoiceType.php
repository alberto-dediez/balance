<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class UseProfileChoiceType extends AbstractType
{
    public function __construct(
        private readonly UseProfileRepository $useProfileRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->useProfileRepository->findByFilter(new UseProfileFilter(limit: 0)) as $useProfile) {
            $choices[$this->label($useProfile, $choices)] = (string) $useProfile->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(UseProfile $useProfile, array $choices): string
    {
        $label = (string) $useProfile;
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
