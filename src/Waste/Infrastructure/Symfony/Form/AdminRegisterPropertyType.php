<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Property\Application\Property\Register\RegisterPropertyRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminRegisterPropertyType extends RegisterPropertyType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPropertyRequest::class,
            'admin' => true,
        ]);
    }
}
