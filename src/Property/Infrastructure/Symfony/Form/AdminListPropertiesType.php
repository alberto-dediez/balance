<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\Property\List\ListPropertiesRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminListPropertiesType extends ListPropertiesType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPropertiesRequest::class,
            'admin' => true,
        ]);
    }
}
