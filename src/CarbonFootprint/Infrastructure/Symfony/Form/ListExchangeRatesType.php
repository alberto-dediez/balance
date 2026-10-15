<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ExchangeRate\List\ListExchangeRatesRequest;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListExchangeRatesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];

        foreach (Unit::currencies() as $currency) {
            if (Unit::Euro === $currency) {
                continue;
            }

            $choices[$currency->value] = $currency;
        }

        $builder
            ->add('currency', UnitType::class, [
                'label' => new TranslatableMessage('Divisa'),
                'choices' => $choices,
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListExchangeRatesRequest::class,
        ]);
    }
}
