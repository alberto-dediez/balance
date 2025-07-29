<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class BrandIdType extends AggregateRootIdType
{
    public function __construct(
        private BrandRepository $countryRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $categories = $this->countryRepository->findByFilter(new BrandFilter(limit: 0));

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($categories),
            'class' => BrandId::class
        ));
    }
}