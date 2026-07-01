<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Application\OvernightStay\Update\EmissionFactorData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<EmissionFactorData> */
class OvernightStayEmissionFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $numberAttr = ['step' => 'any', 'min' => 0];

        $builder
            ->add('year', IntegerType::class, [
                'label' => new TranslatableMessage('Año'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(min: 1900, max: 2200),
                ],
            ])
            ->add('co2eq', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2eq'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('co2', TextType::class, [
                'label' => new TranslatableMessage('Kg CO2'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('ch4', TextType::class, [
                'label' => new TranslatableMessage('g CH4'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('n2o', TextType::class, [
                'label' => new TranslatableMessage('g N2O'),
                'required' => true,
                'attr' => $numberAttr,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'numeric'),
                ],
            ])
            ->add('source', TextType::class, [
                'label' => new TranslatableMessage('Fuente'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('emissionFactorGroupId', EmissionFactorGroupChoiceType::class, [
                'label' => new TranslatableMessage('Grupo'),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmissionFactorData::class,
        ]);
    }
}
