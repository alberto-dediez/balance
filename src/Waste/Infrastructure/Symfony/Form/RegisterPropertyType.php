<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<array<string, mixed>> */
class RegisterPropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
            ])
        ;

        if ($options['admin']) {
            $builder
                ->add('accountId', AccountIdType::class, [
                    'label' => new TranslatableMessage('Cuenta'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Sin cuenta'),
                ])
            ;
        }

        $builder
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPropertyRequest::class,
            'admin' => false,
        ]);
    }
}
