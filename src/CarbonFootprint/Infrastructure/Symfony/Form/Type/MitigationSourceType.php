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

        foreach (Scope::cases() as $scope) {
            foreach ($scope->sections() as $section) {
                $choices[$section->source->value] = $section->source;
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
