<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Application\Property\Register\RegisterPropertyRequest;

class AdminRegisterPropertyType extends RegisterPropertyType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPropertyRequest::class,
            'admin' => true
        ]);
    }
}