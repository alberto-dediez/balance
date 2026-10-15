<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PurchaseService\Export\PurchaseServicesExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\PurchaseServiceCategoryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<PurchaseServicesExportFilter> */
class AdminPurchaseServicesExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('category', PurchaseServiceCategoryType::class, [
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
            'data_class' => PurchaseServicesExportFilter::class,
        ]);
    }
}
