<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialityTopic\List\ListMaterialityTopicsRequest;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityScopeType;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityTopicDirectionType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListMaterialityTopicsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('scope', MaterialityScopeType::class, [
                'label' => new TranslatableMessage('Alcance'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('direction', MaterialityTopicDirectionType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListMaterialityTopicsRequest::class,
        ]);
    }
}
