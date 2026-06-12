<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\CriticalPoint\Dashboard\GetCriticalPointDashboardRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DashboardCriticalPointsType extends ListCriticalPointsType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GetCriticalPointDashboardRequest::class,
        ]);
    }
}
