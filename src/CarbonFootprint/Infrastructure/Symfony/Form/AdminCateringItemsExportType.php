<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CateringItem\Export\CateringItemsExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CateringItemCategoryType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<CateringItemsExportFilter> */
class AdminCateringItemsExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('category', CateringItemCategoryType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
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
            'data_class' => CateringItemsExportFilter::class,
        ]);
    }
}
