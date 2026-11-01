<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Export\CompanyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CompanyCarbonFootprintChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<CompanyCarbonFootprintDetailExportFilter> */
class CompanyCarbonFootprintDetailExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyCarbonFootprintId', CompanyCarbonFootprintChoiceType::class, [
                'label' => new TranslatableMessage('Huella de carbono'),
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanyCarbonFootprintDetailExportFilter::class,
        ]);
    }
}
