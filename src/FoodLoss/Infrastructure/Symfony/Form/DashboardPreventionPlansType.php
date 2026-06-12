<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\PreventionPlan\Dashboard\GetPreventionPlanDashboardRequest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DashboardPreventionPlansType extends ListPreventionPlansType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GetPreventionPlanDashboardRequest::class,
        ]);
    }
}
