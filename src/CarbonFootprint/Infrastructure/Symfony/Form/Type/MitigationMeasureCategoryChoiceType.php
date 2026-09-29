<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class MitigationMeasureCategoryChoiceType extends AbstractType
{
    public function __construct(
        private readonly MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AggregateRootIdTransformer(MitigationMeasureCategoryId::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->mitigationMeasureCategoryRepository->findByFilter(new MitigationMeasureCategoryFilter(limit: 0)) as $category) {
            $choices[(string) $category] = (string) $category->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
