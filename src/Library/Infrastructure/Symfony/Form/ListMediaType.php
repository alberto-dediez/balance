<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Form;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Domain\Model\Media\MediaType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListMediaType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'class' => MediaType::class,
                'label' => new TranslatableMessage('Tipo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los tipos'),
                'choice_translation_domain' => 'mediaType',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListMediaRequest::class,
        ]);
    }
}
