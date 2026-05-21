<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Surplus\List\ListSurplusesRequest;
use App\FoodLoss\Domain\Model\Surplus\FoodType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\CriticalPointIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ServiceCategoryIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\SurplusCauseIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListSurplusesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateFrom', DateType::class, [
                'label' => new TranslatableMessage('Fecha desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dateTo', DateType::class, [
                'label' => new TranslatableMessage('Fecha hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('foodType', EnumType::class, [
                'label' => new TranslatableMessage('Tipo de alimento'),
                'class' => FoodType::class,
                'required' => false,
                'translation_domain' => 'foodType',
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('serviceCategoryId', ServiceCategoryIdType::class, [
                'label' => new TranslatableMessage('Turno'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('criticalPointId', CriticalPointIdType::class, [
                'label' => new TranslatableMessage('Punto crítico'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('surplusCauseId', SurplusCauseIdType::class, [
                'label' => new TranslatableMessage('Motivo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListSurplusesRequest::class,
        ]);
    }
}
