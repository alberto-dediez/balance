<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\PreventionPlan\Register\RegisterPreventionPlanRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PropertyIdsType;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterPreventionPlanType extends AbstractType
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
            ->add('companyId', CompanyIdType::class, [
                'label' => new TranslatableMessage('Empresa'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(message: 'Debes seleccionar una empresa'),
                ],
            ])
            ->add('properties', PropertyIdsType::class, [
                'label' => new TranslatableMessage('Propiedades'),
                'placeholder' => new TranslatableMessage('Todas las propiedades'),
                'required' => false,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPreventionPlanRequest::class,
        ]);
    }
}
