<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class CategoryIdType extends AggregateRootIdType
{
    public function __construct(
        private CategoryRepository $countryRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $categories = $this->countryRepository->findByFilter(new CategoryFilter(limit: 0));

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($categories),
            'class' => CategoryId::class
        ));
    }
}