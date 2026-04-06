<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\CriticalPoint\List\ListCriticalPointsRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ActionIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ServiceCategoryIdType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListCriticalPointsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
            ])
            ->add('serviceCategoryId', ServiceCategoryIdType::class, [
                'label' => new TranslatableMessage('Categoría de servicio'),
                'required' => false,
            ])
            ->add('actionId', ActionIdType::class, [
                'label' => new TranslatableMessage('Acción'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListCriticalPointsRequest::class,
        ]);
    }
}
