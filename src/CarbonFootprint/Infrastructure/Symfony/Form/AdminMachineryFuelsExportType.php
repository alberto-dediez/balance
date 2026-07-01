<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\MachineryFuel\Export\MachineryFuelsExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MachineryFuelMachineryTypeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<MachineryFuelsExportFilter> */
class AdminMachineryFuelsExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('machineryType', MachineryFuelMachineryTypeType::class, [
                'label' => new TranslatableMessage('Tipo de maquinaria'),
                'required' => false,
            ])
            ->add('emissionFactorGroup', EmissionFactorGroupChoiceType::class, [
                'label' => new TranslatableMessage('Grupo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MachineryFuelsExportFilter::class,
        ]);
    }
}
