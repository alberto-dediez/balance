<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\OvernightStay\Update\UpdateOvernightStayRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CategoryOvernightType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\OvernightStayEmissionFactorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateOvernightStayType extends AbstractType
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
            ->add('category', CategoryOvernightType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('emissionFactors', GroupedEmissionFactorsType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => OvernightStayEmissionFactorType::class,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateOvernightStayRequest::class,
        ]);
    }
}
