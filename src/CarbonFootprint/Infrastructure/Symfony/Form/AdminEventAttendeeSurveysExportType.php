<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\AttendeeSurvey\Export\EventAttendeeSurveysExportFilter;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<EventAttendeeSurveysExportFilter> */
class AdminEventAttendeeSurveysExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventId', EventIdType::class, [
                'label' => new TranslatableMessage('Evento'),
                'required' => true,
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventAttendeeSurveysExportFilter::class,
        ]);
    }
}
