<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Dashboard\GetAdminDashboardRequest;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DashboardAdminType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GetAdminDashboardRequest::class,
        ]);
    }
}
