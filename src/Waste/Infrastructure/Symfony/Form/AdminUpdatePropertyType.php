<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Property\Application\Property\Update\UpdatePropertyRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUpdatePropertyType extends UpdatePropertyType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdatePropertyRequest::class,
            'admin' => true,
        ]);
    }
}
