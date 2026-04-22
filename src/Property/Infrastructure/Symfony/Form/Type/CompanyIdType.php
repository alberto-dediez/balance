<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyIdType extends AggregateRootIdType
{
    public function __construct(private readonly CompanyRepository $companyRepository) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => CompanyId::class,
            'account_id' => null,
        ]);

        $resolver->setAllowedTypes('account_id', ['null', AccountId::class]);

        $resolver->setNormalizer('choices', function ($options) {
            $filter = new CompanyFilter(accountId: $options['account_id'], limit: 0);
            $companies = $this->companyRepository->findByFilter($filter);

            return $this->getChoices($companies);
        });
    }
}
