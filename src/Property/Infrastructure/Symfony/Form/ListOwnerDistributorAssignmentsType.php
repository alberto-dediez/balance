<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsRequest;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListOwnerDistributorAssignmentsType extends BaseFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListDistributorAssignmentsRequest::class,
        ]);
    }
}
