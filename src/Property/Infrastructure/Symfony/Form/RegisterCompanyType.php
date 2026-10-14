<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\Company\Register\RegisterCompanyRequest;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('vatNumber', TextType::class, [
                'label' => new TranslatableMessage('CIF'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => new TranslatableMessage('Calle'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 512),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => new TranslatableMessage('Ciudad'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('country', TextType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
        ;

        if ($options['admin']) {
            $builder
                ->add('accountId', AccountIdType::class, [
                    'label' => new TranslatableMessage('Cuenta'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Selecciona una cuenta'),
                ])
            ;
        }

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Registrar'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterCompanyRequest::class,
            'admin' => false,
        ]);
    }
}
