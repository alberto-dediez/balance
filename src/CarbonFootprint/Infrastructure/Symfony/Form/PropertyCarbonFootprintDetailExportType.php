<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Export\PropertyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\PropertyCarbonFootprintChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<PropertyCarbonFootprintDetailExportFilter> */
class PropertyCarbonFootprintDetailExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyCarbonFootprintId', PropertyCarbonFootprintChoiceType::class, [
                'label' => new TranslatableMessage('Huella de carbono'),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PropertyCarbonFootprintDetailExportFilter::class,
        ]);
    }
}
