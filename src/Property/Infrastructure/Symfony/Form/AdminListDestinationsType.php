<?php

namespace App\Property\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use App\Property\Application\Destination\List\ListDestinationsRequest;

class AdminListDestinationsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false
            ])
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los países')
            ])
        ;

        parent::buildForm($builder, $options);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListDestinationsRequest::class,
            'admin' => true
        ]);
    }
}