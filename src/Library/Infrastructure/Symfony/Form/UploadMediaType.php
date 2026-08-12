<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Application\Media\Upload\UploadMediaRequest;
use App\Library\Domain\Model\Media\MediaVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
                'multiple' => true,
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'help' => new TranslatableMessage('Si se deja vacío se usará el nombre del archivo. Solo aplica si se sube un único archivo'),
                'required' => false,
                'empty_data' => '',
            ])
            ->add('alternativeText', TextType::class, [
                'label' => new TranslatableMessage('Texto alternativo'),
                'help' => new TranslatableMessage('Solo aplica si se sube un único archivo'),
                'required' => false,
                'empty_data' => null,
            ])
            ->add('description', TextareaType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'help' => new TranslatableMessage('Solo aplica si se sube un único archivo'),
                'required' => false,
                'empty_data' => null,
            ])
            ->add('visibility', EnumType::class, [
                'class' => MediaVisibility::class,
                'label' => new TranslatableMessage('Visibilidad'),
                'help' => new TranslatableMessage('Quién puede abrir los archivos subidos allí donde se usen'),
                'required' => true,
                'choice_translation_domain' => 'mediaVisibility',
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Subir'),
            ])
        ;

        if (true === $options['shareable']) {
            $builder->add('shared', CheckboxType::class, [
                'label' => new TranslatableMessage('Compartir en la biblioteca'),
                'help' => new TranslatableMessage('Los archivos aparecerán en la biblioteca del resto de cuentas, que podrán usarlos'),
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UploadMediaRequest::class,
            'shareable' => false,
        ]);

        $resolver->setAllowedTypes('shareable', 'bool');
    }
}
