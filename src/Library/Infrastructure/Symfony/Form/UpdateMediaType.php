<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Application\Media\Update\UpdateMediaRequest;
use App\Library\Domain\Model\Media\MediaVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('visibility', EnumType::class, [
                'class' => MediaVisibility::class,
                'label' => new TranslatableMessage('Visibilidad'),
                'help' => new TranslatableMessage('Quién puede abrir este archivo allí donde se use'),
                'required' => true,
                'choice_translation_domain' => 'mediaVisibility',
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;

        // Sharing spreads the file over every account, so it stays in the hands of the
        // administrators: see MediaAuthorizer::canAdmin()
        if (true === $options['shareable']) {
            $builder->add('shared', CheckboxType::class, [
                'label' => new TranslatableMessage('Compartir en la biblioteca'),
                'help' => new TranslatableMessage('El archivo aparecerá en la biblioteca del resto de cuentas, que podrán usarlo'),
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMediaRequest::class,
            'shareable' => false,
        ]);

        $resolver->setAllowedTypes('shareable', 'bool');
    }
}
