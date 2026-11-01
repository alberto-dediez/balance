<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @extends AbstractType<mixed> */
class MitigationSourceType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        $positions = array_flip(array_map(static fn(Source $source): string => $source->value, Source::cases()));

        foreach (Scope::cases() as $scope) {
            $sources = $scope->allSources();
            usort($sources, static fn(Source $a, Source $b): int => $positions[$a->value] <=> $positions[$b->value]);

            foreach ($sources as $source) {
                $choices[$source->value] = $source;
            }
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?Source $source) => $source?->value,
            'group_by' => fn(Source $source) => $this->translator->trans((string) Scope::ofSource($source)?->value, [], 'scope'),
            'translation_domain' => 'source',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
