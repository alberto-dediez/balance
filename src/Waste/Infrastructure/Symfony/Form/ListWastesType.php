<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;

class ListWastesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false
            ])
            ->add('isDangerous', BooleanType::class, [
                'label' => new TranslatableMessage('¿Es peligroso?'),
                'required' => false
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListWastesRequest::class
        ]);
    }
}