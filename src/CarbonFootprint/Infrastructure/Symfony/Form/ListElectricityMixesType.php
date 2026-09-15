<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ElectricityMix\List\ListElectricityMixesRequest;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListElectricityMixesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListElectricityMixesRequest::class,
        ]);
    }
}
