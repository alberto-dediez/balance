<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUpdateCompanyType extends UpdateCompanyType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'admin' => true,
        ]);
    }
}
