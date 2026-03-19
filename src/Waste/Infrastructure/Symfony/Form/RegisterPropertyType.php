<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Property\Application\Property\Register\RegisterPropertyRequest;

class RegisterPropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
        ;

        if ($options['admin']) {
            $builder
                ->add('accountId', AccountIdType::class, [
                    'label' => new TranslatableMessage('Cuenta'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Sin cuenta')
                ])
            ;
        }

        $builder
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPropertyRequest::class,
            'admin' => false
        ]);
    }
}