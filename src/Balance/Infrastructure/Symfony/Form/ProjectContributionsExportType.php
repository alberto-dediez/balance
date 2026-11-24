<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\ProjectContribution\Export\ProjectContributionsExportFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\DataExchange\ProjectContributionsExportationType;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectContributionStatusType;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<ProjectContributionsExportFilter> */
class ProjectContributionsExportType extends AbstractType
{
    public function __construct(
        private readonly ProjectContributionsExportationType $projectContributionsExportationType,
        private readonly ProjectRepository $projectRepository,
        private readonly AccountProjectRepository $accountProjectRepository,
        private readonly AccountRepository $accountRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $canExportEveryAccount = $this->projectContributionsExportationType->canExportEveryAccount();
        $currentAccountId = $this->currentAccountIdProvider->currentAccountId();

        if ($canExportEveryAccount) {
            $builder->add('accountId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
                'choices' => $this->accountChoices(),
                'choice_value' => static fn(?AccountId $accountId): ?string => $accountId?->id(),
            ]);
        }

        $builder->add('projectId', AutocompleteType::class, [
            'label' => new TranslatableMessage('Proyecto'),
            'required' => false,
            'placeholder' => new TranslatableMessage('Todos'),
            'choices' => $canExportEveryAccount ? $this->projectChoices() : $this->accountProjectChoices($currentAccountId),
            'choice_value' => static fn(?ProjectId $projectId): ?string => $projectId?->id(),
        ]);

        if (!$canExportEveryAccount
            && $currentAccountId instanceof AccountId
            && $this->distributorPolicy->isActiveDistributor($currentAccountId)) {
            $builder->add('propertyId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
                'choices' => $this->propertyChoices($currentAccountId),
                'choice_value' => static fn(?PropertyId $propertyId): ?string => $propertyId?->id(),
            ]);
        }

        $builder
            ->add('status', ProjectContributionStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectContributionsExportFilter::class,
        ]);
    }

    /** @return array<string, AccountId> */
    private function accountChoices(): array
    {
        $choices = [];

        foreach ($this->accountRepository->findByFilter(new AccountFilter(limit: 0)) as $account) {
            $choices[$account->name()] = $account->id();
        }

        return $choices;
    }

    /** @return array<string, ProjectId> */
    private function projectChoices(): array
    {
        $choices = [];

        foreach ($this->projectRepository->findByFilter(new ProjectFilter(limit: 0)) as $project) {
            $choices[$project->name()] = $project->id();
        }

        return $choices;
    }

    /** @return array<string, ProjectId> */
    private function accountProjectChoices(?AccountId $accountId): array
    {
        if (!$accountId instanceof AccountId) {
            return [];
        }

        $choices = [];

        foreach ($this->accountProjectRepository->findByFilter(new AccountProjectFilter(accountId: $accountId, limit: 0)) as $accountProject) {
            $projectId = $accountProject->projectId();
            $project = $projectId instanceof ProjectId ? $this->projectRepository->find($projectId) : null;

            if (null !== $project) {
                $choices[$project->name()] = $project->id();
            }
        }

        return $choices;
    }

    /** @return array<string, PropertyId> */
    private function propertyChoices(AccountId $accountId): array
    {
        $choices = [];

        foreach ($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)) as $property) {
            $choices[$property->name()] = $property->id();
        }

        return $choices;
    }
}
