<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Incidence\Register\RegisterIncidenceRequest;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\CriticalPointIdType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterIncidenceType extends AbstractType
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
            ->add('exceptionOccurredOn', DateTimeType::class, [
                'label' => new TranslatableMessage('Fecha y hora'),
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('incidenceType', EnumType::class, [
                'class' => IncidenceType::class,
                'label' => new TranslatableMessage('Tipología'),
                'required' => true,
                'translation_domain' => 'incidenceType',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('originId', CriticalPointIdType::class, [
                'label' => new TranslatableMessage('Punto de origen'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('description', TextareaType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('effect', TextareaType::class, [
                'label' => new TranslatableMessage('Efecto o daño causado'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => true,
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
            'data_class' => RegisterIncidenceRequest::class,
        ]);
    }
}
