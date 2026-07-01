<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\EmissionFactorGroup\Register\RegisterEmissionFactorGroupRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterEmissionFactorGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => new TranslatableMessage('Código'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 50),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('gwpCo2', NumberType::class, [
                'label' => new TranslatableMessage('GWP CO2'),
                'required' => true,
                'scale' => 8,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('gwpCh4', NumberType::class, [
                'label' => new TranslatableMessage('GWP CH4'),
                'required' => true,
                'scale' => 8,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('gwpN2O', NumberType::class, [
                'label' => new TranslatableMessage('GWP N2O'),
                'required' => true,
                'scale' => 8,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterEmissionFactorGroupRequest::class,
        ]);
    }
}
