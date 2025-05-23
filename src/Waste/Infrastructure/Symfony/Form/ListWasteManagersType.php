<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use App\Waste\Application\WasteManager\List\ListWasteManagersRequest;

class ListWasteManagersType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false
            ])
            ->add('isPublic', BooleanType::class, [
                'label' => new TranslatableMessage('¿Es público?'),
                'required' => false
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListWasteManagersRequest::class,
            'admin' => false
        ]);
    }
}