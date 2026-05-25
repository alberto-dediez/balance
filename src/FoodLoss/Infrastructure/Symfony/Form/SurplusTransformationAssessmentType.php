<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Infrastructure\Symfony\Form\Type\ReasonIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\TransformationChannelType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\TransformationProductType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<mixed> */
class SurplusTransformationAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBeTransformed', BooleanType::class, [
                'label' => new TranslatableMessage('¿Se puede transformar en el propio establecimiento? (Ej. fruta madura para mermeladas, pan de ayer para pan rallado).'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('product', TransformationProductType::class, [
                'label' => new TranslatableMessage('¿En qué se va a transformar?'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar tipo de transformación'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeTransformed]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('channel', TransformationChannelType::class, [
                'label' => new TranslatableMessage('Canal de distribución'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar canal'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeTransformed]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('reasonId', ReasonIdType::class, [
                'label' => new TranslatableMessage('Motivo por el que no se puede transformar'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar motivo (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeTransformed]"]',
                    'data-showable-condition-value' => '0',
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'constraints' => [
                new Assert\Callback(function (mixed $data, ExecutionContextInterface $context): void {
                    if (!is_array($data) || true !== $data['canBeTransformed']) {
                        return;
                    }

                    if (null === $data['product']) {
                        $context->buildViolation('transformation.product.not_null')
                            ->atPath('[product]')
                            ->addViolation();
                    }

                    if (null === $data['channel']) {
                        $context->buildViolation('transformation.channel.not_null')
                            ->atPath('[channel]')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
