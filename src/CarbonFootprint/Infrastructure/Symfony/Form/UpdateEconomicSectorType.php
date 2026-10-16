<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\EconomicSector\Update\UpdateEconomicSectorRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CurrencyUnitType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EconomicSectorEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateEconomicSectorType extends AbstractType
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
            ->add('classificationCode', TextType::class, [
                'label' => new TranslatableMessage('Código CNAE/NACE'),
                'help' => new TranslatableMessage('Código CNAE 2009 o NACE Rev. 2, opcional. Debe ser único.'),
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('unit', CurrencyUnitType::class, [
                'label' => new TranslatableMessage('Divisa'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('emissionFactors', GroupedEmissionFactorsType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => EconomicSectorEmissionFactorType::class,
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
            'data_class' => UpdateEconomicSectorRequest::class,
        ]);
    }
}
