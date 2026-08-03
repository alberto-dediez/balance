<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Application\Media\Upload\UploadMediaRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<UploadMediaRequest>
 */
class UploadMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => new TranslatableMessage('Archivo'),
                'required' => true,
                'mapped' => false,
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'help' => new TranslatableMessage('Si se deja vacío se usará el nombre del archivo'),
                'required' => false,
                'empty_data' => '',
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
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Subir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UploadMediaRequest::class,
        ]);
    }
}
