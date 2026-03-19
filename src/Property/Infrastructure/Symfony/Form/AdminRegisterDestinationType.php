<?php

namespace App\Property\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use App\Property\Infrastructure\Symfony\Form\Type\ExternalIdType;
use App\Property\Application\Destination\Register\RegisterDestinationRequest;

class AdminRegisterDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true
            ])
            ->add('externalId', ExternalIdType::class, [
                'label' => new TranslatableMessage('Identificadores externos')
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterDestinationRequest::class,
            'admin' => true
        ]);
    }
}