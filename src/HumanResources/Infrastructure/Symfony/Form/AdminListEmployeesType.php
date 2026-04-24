<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Symfony\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminListEmployeesType extends ListEmployeesType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'admin' => true,
        ]);
    }
}
