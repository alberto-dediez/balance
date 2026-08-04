<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Application\Media\Update\UpdateMediaRequest;
use App\Library\Domain\Model\Media\MediaVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<UpdateMediaRequest>
 */
class UpdateMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
            ])
            ->add('alternativeText', TextType::class, [
                'label' => new TranslatableMessage('Texto alternativo'),
                'required' => false,
                'empty_data' => null,
            ])
            ->add('description', TextareaType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
                'empty_data' => null,
            ])
        ;

        if (true === $options['manage_visibility']) {
            $builder->add('visibility', EnumType::class, [
                'class' => MediaVisibility::class,
                'label' => new TranslatableMessage('Visibilidad'),
                'help' => new TranslatableMessage('Quién puede acceder a este archivo'),
                'required' => true,
                'choice_translation_domain' => 'mediaVisibility',
            ]);
        }

        $builder
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMediaRequest::class,
            'manage_visibility' => false,
        ]);

        $resolver->setAllowedTypes('manage_visibility', 'bool');
    }
}
