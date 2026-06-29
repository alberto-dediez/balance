<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\RoadTransport\List\ListRoadTransportsRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListRoadTransportsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('unit', UnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListRoadTransportsRequest::class,
        ]);
    }
}
