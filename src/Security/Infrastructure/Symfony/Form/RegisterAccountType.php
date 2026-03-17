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

/**
 * @extends AbstractType<RegisterAccountRequest>
 */
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterAccountRequest::class
        ]);
    }
}
