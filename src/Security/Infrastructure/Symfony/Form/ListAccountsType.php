<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\Account\List\ListAccountsRequest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Type\BooleanType;

class ListAccountsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'required' => false
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListAccountsRequest::class
        ]);
    }
}