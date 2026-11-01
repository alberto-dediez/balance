<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\EventCarbonFootprint\Export\EventCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EventCarbonFootprintChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<EventCarbonFootprintDetailExportFilter> */
class EventCarbonFootprintDetailExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventCarbonFootprintId', EventCarbonFootprintChoiceType::class, [
                'label' => new TranslatableMessage('Huella de carbono'),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventCarbonFootprintDetailExportFilter::class,
        ]);
    }
}
