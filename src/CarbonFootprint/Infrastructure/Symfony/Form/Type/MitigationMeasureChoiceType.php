<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class MitigationMeasureChoiceType extends AbstractType
{
    public function __construct(
        private readonly MitigationMeasureRepository $mitigationMeasureRepository,
        private readonly MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AggregateRootIdTransformer(MitigationMeasureId::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->mitigationMeasureRepository->findByFilter(new MitigationMeasureFilter(limit: 0)) as $measure) {
            $categoryName = $this->categoryName($measure->categoryId());
            $choices[$categoryName][$measure->name()] = (string) $measure->id();
        }

        ksort($choices);
        foreach ($choices as &$measures) {
            ksort($measures);
        }
        unset($measures);

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_translation_domain' => false,
        ]);
    }

    private function categoryName(?MitigationMeasureCategoryId $categoryId): string
    {
        $category = null !== $categoryId ? $this->mitigationMeasureCategoryRepository->find($categoryId) : null;

        return null !== $category ? $category->name() : '---';
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
