<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Application\AlertConfiguration\List\ListAlertConfigurationsRequest;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListAlertConfigurationsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListAlertConfigurationsRequest::class,
        ]);
    }
}
