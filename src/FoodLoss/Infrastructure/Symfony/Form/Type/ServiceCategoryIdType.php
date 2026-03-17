<?php

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceCategoryIdType extends AggregateRootIdType
{
    public function __construct(
        private ServiceCategoryRepository $serviceCategoryRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $serviceCategories = $this->serviceCategoryRepository->findByFilter(
            new ServiceCategoryFilter(limit: 0)
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($serviceCategories),
            'class' => ServiceCategoryId::class
        ));
    }
}