<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Security\Application\Account\Register\RegisterAccountRequest;

class RegisterAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => true
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegisterAccountRequest::class
        ]);
    }
}