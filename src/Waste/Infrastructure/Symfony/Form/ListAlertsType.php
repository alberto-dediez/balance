<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Application\Alert\List\ListAlertsRequest;
use App\Waste\Infrastructure\Symfony\Form\Type\AlertPriorityType;
use App\Waste\Infrastructure\Symfony\Form\Type\AlertStatusType;
use App\Waste\Infrastructure\Symfony\Form\Type\AlertTypeType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListAlertsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las propiedades'),
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las tipologías'),
            ])
            ->add('type', AlertTypeType::class, [
                'label' => new TranslatableMessage('Tipo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los tipos'),
            ])
            ->add('status', AlertStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los estados'),
            ])
            ->add('priority', AlertPriorityType::class, [
                'label' => new TranslatableMessage('Prioridad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las prioridades'),
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListAlertsRequest::class,
        ]);
    }
}
