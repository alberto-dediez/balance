<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

final class ImportDecisionsResolver
{
    public function __construct(
        private AccountRepository $accountRepository,
        private PropertyRepository $propertyRepository,
        private MobilityRepository $mobilityRepository,
        private ProjectRepository $projectRepository,
    ) {}

    public function resolve(LegacyBalanceDataset $dataset, ImportOptions $options, string $legacyUrl, bool $interactive): ImportDecisions
    {
        $decisions = new ImportDecisions($legacyUrl);

        foreach ($dataset->organizations as $organization) {
            $decision = $this->organization($organization, $options);
            $decision->propertyId = $this->property($organization, $decision, $options);

            $decisions->organizations[$organization->slug] = $decision;
        }

        foreach ($this->transports($dataset, $decisions) as $transport) {
            $mobilityId = $this->mobility($transport, $options, $interactive);

            if (null !== $mobilityId) {
                $decisions->mobilities[$transport] = $mobilityId;
            }
        }

        foreach ($this->referencedEcoProjectIds($dataset, $decisions) as $ecoProjectId) {
            $ecoProject = $dataset->ecoProjectById($ecoProjectId);

            $decisions->projects[$ecoProjectId] = null !== $ecoProject ? $this->project($ecoProject, $options) : null;
        }

        return $decisions;
    }

    public function organization(LegacyOrganization $organization, ImportOptions $options): OrganizationDecision
    {
        $staff = in_array($organization->slug, $options->staff, true);

        if (in_array($organization->slug, $options->skip, true)) {
            return new OrganizationDecision($organization->slug, OrganizationTarget::Skip, staff: $staff);
        }

        if (isset($options->accounts[$organization->slug])) {
            $account = $this->accountRepository->find(new AccountId($options->accounts[$organization->slug]));

            if (!$account instanceof Account) {
                throw new \InvalidArgumentException(sprintf('La cuenta "%s" indicada en --account para "%s" no existe.', $options->accounts[$organization->slug], $organization->slug));
            }

            return new OrganizationDecision($organization->slug, OrganizationTarget::Reuse, $account->id(), $staff);
        }

        $account = $this->accountRepository->findByName($organization->displayName);

        if ($account instanceof Account) {
            return new OrganizationDecision($organization->slug, OrganizationTarget::Reuse, $account->id(), $staff);
        }

        return new OrganizationDecision($organization->slug, OrganizationTarget::Create, staff: $staff);
    }

    public function property(LegacyOrganization $organization, OrganizationDecision $decision, ImportOptions $options): ?PropertyId
    {
        if ($organization->isOta() || $decision->isSkipped()) {
            return null;
        }

        if (isset($options->properties[$organization->slug])) {
            $property = $this->propertyRepository->find(new PropertyId($options->properties[$organization->slug]));

            if (!$property instanceof Property) {
                throw new \InvalidArgumentException(sprintf('La propiedad "%s" indicada en --property para "%s" no existe.', $options->properties[$organization->slug], $organization->slug));
            }

            $propertyAccountId = $property->accountId();

            if (null === $decision->accountId || null === $propertyAccountId || !$propertyAccountId->equals($decision->accountId)) {
                throw new \InvalidArgumentException(sprintf('La propiedad "%s" indicada en --property para "%s" no pertenece a su cuenta.', $options->properties[$organization->slug], $organization->slug));
            }

            return $property->id();
        }

        if (null === $decision->accountId) {
            return null;
        }

        $properties = $this->properties($decision->accountId);

        return 1 === count($properties) ? $properties[0]->id() : null;
    }

    public function mobility(string $transport, ImportOptions $options, bool $interactive): ?MobilityId
    {
        if (isset($options->mobilities[$transport])) {
            $mobility = $this->mobilityRepository->find(new MobilityId($options->mobilities[$transport]));

            if (!$mobility instanceof Mobility) {
                throw new \InvalidArgumentException(sprintf('El medio de transporte "%s" indicado en --mobility-%s no existe.', $options->mobilities[$transport], $transport));
            }

            return $mobility->id();
        }

        if (!$interactive) {
            throw new \InvalidArgumentException(sprintf('Falta la opción --mobility-%s: hay cálculos en "%s" que importar.', $transport, $transport));
        }

        return null;
    }

    public function project(LegacyEcoProject $ecoProject, ImportOptions $options): ?ProjectId
    {
        if (isset($options->projects[$ecoProject->id])) {
            $project = $this->projectRepository->find(new ProjectId($options->projects[$ecoProject->id]));

            if (!$project instanceof Project) {
                throw new \InvalidArgumentException(sprintf('El proyecto "%s" indicado en --project para "%s" no existe.', $options->projects[$ecoProject->id], $ecoProject->id));
            }

            return $project->id();
        }

        $name = self::normalizeName($ecoProject->displayName);

        foreach ($this->projects() as $project) {
            if (self::normalizeName($project->name()) === $name) {
                return $project->id();
            }
        }

        return null;
    }

    /** @return list<LegacyCalculation> */
    public function importableCalculations(LegacyBalanceDataset $dataset, ImportDecisions $decisions): array
    {
        return array_values(array_filter(
            $dataset->calculations,
            fn(LegacyCalculation $calculation): bool => $this->isImportable($calculation, $dataset, $decisions),
        ));
    }

    /** @return list<string> */
    public function transports(LegacyBalanceDataset $dataset, ImportDecisions $decisions): array
    {
        $transports = array_map(
            static fn(LegacyCalculation $calculation): string => $calculation->transport,
            $this->importableCalculations($dataset, $decisions),
        );

        return array_values(array_unique($transports));
    }

    /** @return list<string> */
    public function referencedEcoProjectIds(LegacyBalanceDataset $dataset, ImportDecisions $decisions): array
    {
        $ecoProjectIds = [];

        foreach ($dataset->donations as $donation) {
            $organization = $dataset->organizationById($donation->organizationId);
            $decision = null !== $organization ? $decisions->organization($organization->slug) : null;

            if (null !== $decision && !$decision->isSkipped()) {
                $ecoProjectIds[] = $donation->ecoProjectId;
            }
        }

        return array_values(array_unique($ecoProjectIds));
    }

    /** @return list<Account> */
    public function accounts(): array
    {
        return $this->accountRepository->findByFilter(new AccountFilter(limit: 0));
    }

    /** @return list<Property> */
    public function properties(AccountId $accountId): array
    {
        return array_values($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)));
    }

    /** @return list<Mobility> */
    public function mobilities(): array
    {
        return array_values($this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0)));
    }

    /** @return list<Project> */
    public function projects(): array
    {
        return array_values($this->projectRepository->findByFilter(new ProjectFilter(limit: 0)));
    }

    public static function normalizeName(string $name): string
    {
        $normalized = \Normalizer::normalize(mb_strtolower($name), \Normalizer::FORM_D);
        $normalized = preg_replace('/\p{Mn}+/u', '', false !== $normalized ? $normalized : $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    private function isImportable(LegacyCalculation $calculation, LegacyBalanceDataset $dataset, ImportDecisions $decisions): bool
    {
        if (null === $calculation->referralSlug) {
            return false;
        }

        $organization = $dataset->organizationBySlug($calculation->referralSlug);
        $decision = $decisions->organization($calculation->referralSlug);

        if (null === $organization || null === $decision || $decision->isSkipped()) {
            return false;
        }

        return $organization->isOta() || null !== $decision->propertyId;
    }
}
