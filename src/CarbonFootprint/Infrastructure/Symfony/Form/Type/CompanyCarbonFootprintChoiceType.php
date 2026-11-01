<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CompanyCarbonFootprintChoiceType extends AbstractType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AggregateRootIdTransformer(CompanyCarbonFootprintId::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;
        $account = $loggedAccount();

        if (null === $account) {
            $resolver->setDefaults([
                'choices' => [],
            ]);

            return;
        }

        $footprints = $this->companyCarbonFootprintRepository->findByFilter(new CompanyCarbonFootprintFilter(
            status: OrganizationalCarbonFootprintStatus::Completed,
            accountId: $account->id(),
            limit: 0,
        ));

        $choices = [];

        foreach ($footprints as $footprint) {
            $choices[$this->label($footprint, $choices)] = (string) $footprint->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }

    /** @param array<string, string> $choices */
    private function label(CompanyCarbonFootprint $footprint, array $choices): string
    {
        $companyId = $footprint->companyId();
        $company = null !== $companyId ? $this->companyRepository->find($companyId) : null;

        $label = null !== $company ? sprintf('%s — %d', $company->name(), $footprint->year()) : (string) $footprint->id();

        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }
}
