<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialityTopic\Update\UpdateMaterialityTopicRequest;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityScopeType;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityTopicDirectionType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateMaterialityTopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scope', MaterialityScopeType::class, [
                'label' => new TranslatableMessage('Alcance'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('direction', MaterialityTopicDirectionType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin dirección'),
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('description', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
                'empty_data' => '',
                'height' => '220px',
                'hide_link' => false,
            ])
            ->add('position', IntegerType::class, [
                'label' => new TranslatableMessage('Posición'),
                'required' => true,
                'constraints' => [new Assert\Range(min: 0)],
            ])
            ->add('active', CheckboxType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => false,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMaterialityTopicRequest::class,
        ]);
    }
}
