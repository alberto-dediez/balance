<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearRequest;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<DeclareBaseYearRequest> */
class DeclareBaseYearType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<int> $years */
        $years = $options['years'];

        $builder
            ->add('year', ChoiceType::class, [
                'label' => new TranslatableMessage('Año base'),
                'choices' => array_combine(array_map('strval', $years), $years),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('justification', TextareaType::class, [
                'label' => new TranslatableMessage('Justificación del año base'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('recalculationThresholdPercent', NumberType::class, [
                'label' => new TranslatableMessage('Umbral de significancia (%)'),
                'scale' => 2,
                'input' => 'string',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive(),
                    new Assert\LessThanOrEqual(100),
                ],
            ])
            ->add('primaryIndicator', EnumType::class, [
                'label' => new TranslatableMessage('Indicador principal'),
                'class' => IntensityIndicator::class,
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin indicador principal'),
                'choice_label' => static fn(IntensityIndicator $indicator): string => $indicator->value,
                'choice_translation_domain' => 'intensityIndicator',
            ])
            ->add('declare', SubmitType::class, [
                'label' => $options['declared'] ? new TranslatableMessage('Actualizar declaración') : new TranslatableMessage('Declarar año base'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeclareBaseYearRequest::class,
            'years' => [],
            'declared' => false,
        ]);
        $resolver->setAllowedTypes('years', 'array');
        $resolver->setAllowedTypes('declared', 'bool');
    }
}
