<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Material\Export\MaterialsExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialCategoryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<MaterialsExportFilter> */
class AdminMaterialsExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('category', MaterialCategoryType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaterialsExportFilter::class,
        ]);
    }
}
