<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Application\DailyWaste\List\ListDailyWastesRequest;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListDailyWastesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('properties', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedades'),
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'placeholder' => new TranslatableMessage('Todas las propiedades'),
                ],
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las tipologías'),
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Destino'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los destinos'),
            ])
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los gestores'),
            ])
            ->add('day', DateType::class, [
                'label' => new TranslatableMessage('Fecha'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las fechas'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListDailyWastesRequest::class,
        ]);
    }
}
