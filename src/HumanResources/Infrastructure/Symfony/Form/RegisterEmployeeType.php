<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Symfony\Form;

use App\HumanResources\Application\Employee\Register\RegisterEmployeeRequest;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterEmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre completo'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => new TranslatableMessage('Teléfono'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => new TranslatableMessage('Calle'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 10),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => new TranslatableMessage('Ciudad'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('country', TextType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 100),
                ],
            ])
        ;

        if ($options['admin']) {
            $builder
                ->add(
                    'accountId',
                    AccountIdType::class,
                    [
                        'label' => new TranslatableMessage('Cuenta'),
                        'required' => true,
                        'constraints' => [
                            new Assert\NotNull(),
                        ],
                    ],
                )
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
            'data_class' => RegisterEmployeeRequest::class,
            'admin' => false,
        ]);
    }
}
