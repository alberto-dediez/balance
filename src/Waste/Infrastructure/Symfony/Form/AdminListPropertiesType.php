<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Application\Property\List\ListPropertiesRequest;

class AdminListPropertiesType extends ListPropertiesType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPropertiesRequest::class,
            'admin' => true
        ]);
    }
}