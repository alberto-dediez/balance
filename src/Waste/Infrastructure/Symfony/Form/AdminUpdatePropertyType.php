<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Application\Property\Update\UpdatePropertyRequest;

class AdminUpdatePropertyType extends UpdatePropertyType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UpdatePropertyRequest::class,
            'admin' => true
        ]);
    }
}