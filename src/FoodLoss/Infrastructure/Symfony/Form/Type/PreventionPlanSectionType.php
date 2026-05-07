<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Application\PreventionPlan\UpdateSections\SectionData;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSectionId;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PreventionPlanSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sectionId', HiddenType::class)
            ->add('title', TextType::class, [
                'label' => false,
            ])
            ->add('content', MarkdownEditorType::class, [
                'label' => false,
            ])
        ;

        $builder->get('sectionId')->addModelTransformer(
            new CallbackTransformer(
                fn(?PreventionPlanSectionId $id): string => null !== $id ? (string) $id : '',
                fn(?string $value): ?PreventionPlanSectionId => !empty($value) ? new PreventionPlanSectionId($value) : null,
            ),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SectionData::class,
        ]);
    }
}
