<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialitySettings\Update\UpdateMaterialitySettingsRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateMaterialitySettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('relevanceThresholdPercent', IntegerType::class, [
                'label' => new TranslatableMessage('Umbral de relevancia (%)'),
                'help' => new TranslatableMessage('Relevancia mínima, en porcentaje, para calcular una categoría del alcance 3'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(min: 1, max: 100),
                ],
            ])
            ->add('highMagnitudeForcesInclusion', CheckboxType::class, [
                'label' => new TranslatableMessage('Una magnitud alta obliga a calcular la categoría aunque no llegue al umbral'),
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMaterialitySettingsRequest::class,
        ]);
    }
}
