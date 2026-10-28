<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Console\LegacyImport;

use App\Balance\Application\ProjectContribution\ImportPaid\ImportPaidProjectContributionRequest;
use App\Balance\Application\ProjectContribution\ImportPaid\ImportPaidProjectContributionService;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\DuplicatedProjectContributionException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\LegacyImport\ImportDecisions;
use App\Balance\Infrastructure\LegacyImport\ImportDecisionsResolver;
use App\Balance\Infrastructure\LegacyImport\ImportOptions;
use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDataset;
use App\Balance\Infrastructure\LegacyImport\LegacyBalanceDatasetReader;
use App\Balance\Infrastructure\LegacyImport\LegacyCalculation;
use App\Balance\Infrastructure\LegacyImport\LegacyCalculationMapper;
use App\Balance\Infrastructure\LegacyImport\LegacyDonation;
use App\Balance\Infrastructure\LegacyImport\LegacyGroupAssignment;
use App\Balance\Infrastructure\LegacyImport\LegacyGroups;
use App\Balance\Infrastructure\LegacyImport\LegacyGroupsReader;
use App\Balance\Infrastructure\LegacyImport\LegacyMembership;
use App\Balance\Infrastructure\LegacyImport\LegacyOrganization;
use App\Balance\Infrastructure\LegacyImport\LegacyUser;
use App\Balance\Infrastructure\LegacyImport\OrganizationDecision;
use App\Balance\Infrastructure\LegacyImport\OrganizationTarget;
use App\Balance\Infrastructure\LegacyImport\RedirectRule;
use App\Balance\Infrastructure\LegacyImport\RedirectRulesWriter;
use App\CarbonFootprint\Application\TripCalculation\Import\ImportTripCalculationService;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkService;
use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use App\Security\Application\Account\AssignConsultant\AssignConsultantRequest;
use App\Security\Application\Account\AssignConsultant\AssignConsultantService;
use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusRequest;
use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusService;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'balance:legacy:import',
    description: 'Importa paso a paso cuentas, usuarios, cálculos y aportaciones de una plataforma Balance y genera sus redirecciones',
)]
class ImportLegacyBalanceCommand extends Command
{
    private const array TRANSPORTS = ['plane', 'car', 'train', 'bus'];

    private const string SKIP = 'Omitir';

    private const string NO_PROPERTY = 'Sin propiedad (dejar hueco en la redirección y omitir sus cálculos)';

    private const string NO_PROJECT = 'Sin equivalente (omitir sus donaciones)';

    private const string NO_CONSULTANT = 'Ninguna';

    private const string DRY_RUN_MARKER = '{{DRY_RUN}}';

    private const string REDIRECTS_FILE = 'redirects.txt';

    private const string CREATED = 'creados';

    private const string REUSED = 'reutilizados';

    private const string SKIPPED = 'omitidos';

    private const string ERRORS = 'errores';

    private SymfonyStyle $io;

    private User $user;

    private bool $dryRun = false;

    private bool $askConsultant = false;

    /** @var array<string, array<string, int>> */
    private array $summary = [];

    public function __construct(
        private readonly LegacyBalanceDatasetReader $datasetReader,
        private readonly LegacyGroupsReader $groupsReader,
        private readonly ImportDecisionsResolver $decisionsResolver,
        private readonly RedirectRulesWriter $redirectRulesWriter,
        private readonly LegacyCalculationMapper $legacyCalculationMapper,
        private readonly UserRepository $userRepository,
        private readonly RegisterAccountService $registerAccount,
        private readonly RegisterUserService $registerUser,
        private readonly GrantRoleService $grantRole,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly RegisterAccountTripCalculationLinkService $registerAccountTripCalculationLink,
        private readonly RegisterTripCalculationLinkService $registerTripCalculationLink,
        private readonly TripCalculationRepository $tripCalculationRepository,
        private readonly ImportTripCalculationService $importTripCalculation,
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectContributionRepository $projectContributionRepository,
        private readonly ImportPaidProjectContributionService $importPaidProjectContribution,
        private readonly RegisterPropertyService $registerProperty,
        private readonly ChangeConsultantStatusService $changeConsultantStatus,
        private readonly AssignConsultantService $assignConsultant,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Directorio con los JSON exportados de la plataforma Balance')
            ->addOption('legacy-url', null, InputOption::VALUE_REQUIRED, 'URL de la plataforma antigua (p. ej. https://balance.bioscore.info)')
            ->addOption('skip', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Organización a omitir (slug)')
            ->addOption('staff', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Organización cuyos miembros son personal de la plataforma (slug, o nombre del grupo si está agrupada)')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Cuenta existente de una organización (slug=accountId)')
            ->addOption('property', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Propiedad de una organización (slug=propertyId)')
            ->addOption('project', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Proyecto equivalente de un proyecto antiguo (oldEcoProjectId=projectId)')
            ->addOption('mobility-plane', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en avión (MobilityId)')
            ->addOption('mobility-car', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en coche (MobilityId)')
            ->addOption('mobility-train', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en tren (MobilityId)')
            ->addOption('mobility-bus', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en autobús (MobilityId)')
            ->addOption('groups', null, InputOption::VALUE_REQUIRED, 'Fichero CSV con el grupo (cuenta) de cada organización')
            ->addOption('consultant', null, InputOption::VALUE_REQUIRED, 'Organización que será la consultora de las demás cuentas (slug)')
            ->addOption('create-properties', null, InputOption::VALUE_NONE, 'Crea una propiedad por organización cuando su cuenta no tiene una equivalente')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Pregunta igual pero no escribe en base de datos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->user = UserMother::systemUser();
        $this->dryRun = (bool) $input->getOption('dry-run');
        $this->summary = [];

        $this->io->title('Importación de la plataforma Balance');

        if ($this->dryRun) {
            $this->io->note('Simulación (--dry-run): no se escribe nada en base de datos.');
        }

        $directory = $this->directory((string) $input->getArgument('directory'));

        $this->io->section('Lectura');

        try {
            $dataset = $this->datasetReader->read($directory);
        } catch (\RuntimeException|\JsonException $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $groupsFile = $input->getOption('groups');

        try {
            $groups = is_string($groupsFile) && '' !== $groupsFile
                ? $this->groupsReader->read($this->directory($groupsFile), $dataset)
                : LegacyGroups::empty();
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->showTotals($dataset, $groups);

        $legacyUrl = $this->legacyUrl($input);

        if (null === $legacyUrl) {
            return Command::FAILURE;
        }

        try {
            $options = $this->options($input);
            $decisions = $this->decisionsResolver->resolve($dataset, $options, $groups, $legacyUrl, $input->isInteractive());
        } catch (\InvalidArgumentException $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->reportOrphanMemberships($dataset);

        $this->askConsultant = null !== $options->consultant || $options->createProperties || is_string($groupsFile);

        try {
            $this->consultantChoiceStep($dataset, $decisions);
            $this->organizationsStep($dataset, $decisions);
            $this->propertiesStep($dataset, $decisions, $options);
            $this->consultantAssignmentStep($dataset, $decisions);
            $this->usersStep($dataset, $decisions);
            $this->calculationsStep($dataset, $decisions);
            $this->contributionsStep($dataset, $decisions);
            $this->redirectsStep($dataset, $decisions, $directory);
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->showSummary();

        return Command::SUCCESS;
    }

    private function directory(string $directory): string
    {
        return str_starts_with($directory, '/') ? $directory : $this->projectDir . '/' . $directory;
    }

    private function showTotals(LegacyBalanceDataset $dataset, LegacyGroups $groups): void
    {
        $groupNames = array_unique(array_filter(array_map(
            static fn(LegacyGroupAssignment $assignment): ?string => $assignment->group,
            array_values($groups->assignments),
        )));

        $this->io->table(
            ['Fichero', 'Registros'],
            [
                ['Organization.json', count($dataset->organizations)],
                ['User.json', count($dataset->users)],
                ['OrganizationMember.json', count($dataset->memberships)],
                ['CalculatedEmissions.json', count($dataset->calculations)],
                ['Donation.json', count($dataset->donations)],
                ['EcoProject.json', count($dataset->ecoProjects)],
                ['Grupos (CSV)', sprintf('%d asignaciones, %d grupos', count($groups->assignments), count($groupNames))],
            ],
        );
    }

    private function legacyUrl(InputInterface $input): ?string
    {
        $legacyUrl = $input->getOption('legacy-url');

        if (null === $legacyUrl || '' === $legacyUrl) {
            if (!$input->isInteractive()) {
                $this->io->error('Falta la opción --legacy-url con la URL de la plataforma antigua.');

                return null;
            }

            $legacyUrl = $this->io->ask('URL de la plataforma antigua', null, static function (?string $value): string {
                if (false === filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException('La URL no es válida.');
                }

                return (string) $value;
            });
        }

        if (false === filter_var($legacyUrl, FILTER_VALIDATE_URL)) {
            $this->io->error(sprintf('La opción --legacy-url "%s" no es una URL válida.', (string) $legacyUrl));

            return null;
        }

        return (string) $legacyUrl;
    }

    private function options(InputInterface $input): ImportOptions
    {
        $mobilities = [];

        foreach (self::TRANSPORTS as $transport) {
            $mobilityId = $input->getOption('mobility-' . $transport);

            if (is_string($mobilityId) && '' !== $mobilityId) {
                $mobilities[$transport] = $mobilityId;
            }
        }

        return new ImportOptions(
            skip: $this->listOption($input, 'skip'),
            staff: $this->listOption($input, 'staff'),
            accounts: $this->pairsOption($input, 'account'),
            properties: $this->pairsOption($input, 'property'),
            projects: $this->pairsOption($input, 'project'),
            mobilities: $mobilities,
            consultant: is_string($input->getOption('consultant')) && '' !== $input->getOption('consultant') ? $input->getOption('consultant') : null,
            createProperties: (bool) $input->getOption('create-properties'),
        );
    }

    /** @return list<string> */
    private function listOption(InputInterface $input, string $name): array
    {
        $values = $input->getOption($name);

        return array_values(array_map(strval(...), is_array($values) ? $values : array_filter([$values], is_string(...))));
    }

    /** @return array<string, string> */
    private function pairsOption(InputInterface $input, string $name): array
    {
        $pairs = [];

        foreach ($this->listOption($input, $name) as $value) {
            $parts = explode('=', $value, 2);

            if (2 !== count($parts) || '' === $parts[0] || '' === $parts[1]) {
                throw new \InvalidArgumentException(sprintf('La opción --%s="%s" debe tener el formato clave=valor.', $name, $value));
            }

            $pairs[$parts[0]] = $parts[1];
        }

        return $pairs;
    }

    private function reportOrphanMemberships(LegacyBalanceDataset $dataset): void
    {
        $orphans = [];

        foreach ($dataset->memberships as $membership) {
            if (!$dataset->userById($membership->userId) instanceof LegacyUser) {
                $orphans[] = sprintf('Pertenencia del usuario inexistente %s a %s: omitida', $membership->userId, $membership->organizationId);
            }

            if (!$dataset->organizationById($membership->organizationId) instanceof LegacyOrganization) {
                $orphans[] = sprintf('Pertenencia de %s a la organización inexistente %s: omitida', $membership->userId, $membership->organizationId);
            }
        }

        if ([] !== $orphans) {
            $this->io->warning($orphans);
        }
    }

    private function consultantChoiceStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        if (!$this->askConsultant) {
            return;
        }

        $this->io->section('Consultora');

        $choices = [];

        foreach ($dataset->organizations as $organization) {
            $label = sprintf('%s (%s)', $organization->displayName, $organization->slug);

            if (!$this->decision($decisions, $organization)->isSkipped() && !isset($choices[$label])) {
                $choices[$label] = $organization->id;
            }
        }

        $default = null !== $decisions->consultantOrganizationId ? array_search($decisions->consultantOrganizationId, $choices, true) : false;

        $answer = $this->io->choice(
            'Organización consultora',
            array_merge([self::NO_CONSULTANT], array_keys($choices)),
            false !== $default ? $default : self::NO_CONSULTANT,
        );

        $consultantOrganizationId = $choices[$answer] ?? null;

        foreach ($decisions->organizations as $organizationId => $decision) {
            $decision->consultant = $organizationId === $consultantOrganizationId;
        }

        $decisions->consultantOrganizationId = $consultantOrganizationId;
        $consultant = $decisions->consultantDecision();

        if (null !== $consultant && count($decisions->decisionsOfAccountKey($consultant->accountKey)) > 1) {
            throw new \InvalidArgumentException('La consultora no puede compartir cuenta con otras organizaciones.');
        }
    }

    private function organizationsStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $this->io->section('Organizaciones y cuentas');

        $this->missingGroupAccounts($dataset, $decisions);

        $accounts = [];

        foreach ($this->decisionsResolver->accounts() as $account) {
            $accounts[sprintf('%s (%s)', $account->name(), $account->id())] = $account->id();
        }

        foreach ($this->accountGroups($dataset, $decisions) as $members) {
            [$organization, $decision] = $members[0];
            $create = sprintf('Crear cuenta nueva "%s"', $this->accountName($organization, $decision));

            $default = match ($decision->target) {
                OrganizationTarget::Skip => self::SKIP,
                OrganizationTarget::Create => $create,
                OrganizationTarget::Reuse => (string) array_search($decision->accountId, $accounts, false),
            };

            $question = null === $decision->group
                ? sprintf('Destino de la organización "%s" (%s, %s)', $organization->displayName, $organization->slug, $organization->type)
                : sprintf(
                    'Destino del grupo "%s" (%d organizaciones: %s)',
                    $decision->group,
                    count($members),
                    implode(', ', array_map(static fn(array $member): string => $member[0]->slug, $members)),
                );

            $answer = $this->io->choice(
                $question,
                array_merge([self::SKIP, $create], array_keys($accounts)),
                '' !== $default ? $default : $create,
            );

            $target = match ($answer) {
                self::SKIP => OrganizationTarget::Skip,
                $create => OrganizationTarget::Create,
                default => OrganizationTarget::Reuse,
            };
            $staff = $this->io->confirm(
                sprintf('¿Los miembros de "%s" son personal de la plataforma y no deben importarse?', $decision->group ?? $organization->slug),
                $decision->staff,
            );

            foreach ($members as [, $member]) {
                $member->target = $target;
                $member->accountId = OrganizationTarget::Reuse === $target ? $accounts[$answer] : null;
                $member->staff = $staff;
            }
        }

        $rows = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            $rows[] = [
                $organization->slug,
                $decision->group ?? '-',
                $organization->type,
                $this->targetLabel($decision->target),
                null !== $decision->accountId ? (string) $decision->accountId : '-',
                $decision->staff ? 'sí' : 'no',
            ];

            if ($decision->isSkipped()) {
                $this->count('Cuentas', self::SKIPPED);
            }
        }

        $this->io->table(['Organización', 'Grupo', 'Tipo', 'Destino', 'Cuenta', 'Personal'], $rows);

        $toCreate = [];

        foreach ($this->accountGroups($dataset, $decisions) as $members) {
            $target = $members[0][1]->target;

            if (OrganizationTarget::Reuse === $target) {
                $this->count('Cuentas', self::REUSED);
            }

            if (OrganizationTarget::Create === $target) {
                $toCreate[] = $members;
            }
        }

        $this->markConsultantAccount($decisions);

        if ([] === $toCreate) {
            return;
        }

        if (!$this->io->confirm('¿Crear las cuentas marcadas como "Crear"?', true)) {
            foreach ($toCreate as $members) {
                foreach ($members as [, $decision]) {
                    $decision->target = OrganizationTarget::Skip;
                    $this->count('Cuentas', self::SKIPPED);
                }
            }

            $this->io->warning('Paso omitido: las organizaciones marcadas como "Crear" se omiten en el resto de pasos.');

            return;
        }

        foreach ($toCreate as $members) {
            [$organization, $decision] = $members[0];
            $name = $this->accountName($organization, $decision);

            if ($this->dryRun) {
                $this->io->writeln(sprintf('Cuenta "%s": se crearía', $name));
                $this->count('Cuentas', self::CREATED);

                continue;
            }

            $active = [] !== array_filter($members, static fn(array $member): bool => $member[0]->active);

            try {
                $response = $this->registerAccount->__invoke(
                    new RegisterAccountRequest(name: $name, active: $active, consultant: $decision->consultant),
                    $this->user,
                );

                foreach ($members as [, $member]) {
                    $member->accountId = $response->accountId;
                }

                $this->io->writeln(sprintf('Cuenta "%s" creada: %s', $name, $response->accountId));
                $this->count('Cuentas', self::CREATED);
            } catch (\Throwable $exception) {
                foreach ($members as [, $member]) {
                    $member->target = OrganizationTarget::Skip;
                }

                $this->error('Cuentas', sprintf('Cuenta "%s": %s', $name, $exception->getMessage()));
            }
        }
    }

    private function missingGroupAccounts(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $missing = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            if ($decision->isSkipped() || null === $decision->group || OrganizationTarget::Create !== $decision->target || null !== $decision->accountId) {
                continue;
            }

            $missing[$decision->group] = ($missing[$decision->group] ?? 0) + 1;
        }

        if ([] === $missing) {
            return;
        }

        $rows = [];

        foreach ($missing as $group => $organizations) {
            $similar = array_map(
                static fn(Account $account): string => sprintf('%s (%s)', $account->name(), $account->id()),
                $this->decisionsResolver->similarAccounts((string) $group),
            );

            $rows[] = [(string) $group, $organizations, [] !== $similar ? implode("\n", $similar) : 'ninguna'];
        }

        $this->io->section('Grupos sin cuenta en Balance');
        $this->io->table(['Grupo', 'Organizaciones', 'Cuentas parecidas'], $rows);
        $this->io->warning(sprintf(
            '%d grupos se crearán como cuentas nuevas. Si alguno debía ir a una cuenta existente, corrige "group" en el CSV o usa --account=<grupo>=<accountId>.',
            count($missing),
        ));

        $this->count('Grupos sin cuenta', self::SKIPPED, count($missing));
    }

    /** @return list<non-empty-list<array{0: LegacyOrganization, 1: OrganizationDecision}>> */
    private function accountGroups(LegacyBalanceDataset $dataset, ImportDecisions $decisions): array
    {
        $all = [];
        $pending = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);
            $all[$decision->accountKey][] = [$organization, $decision];

            if (!$decision->isSkipped()) {
                $pending[$decision->accountKey][] = [$organization, $decision];
            }
        }

        $groups = [];

        foreach ($all as $key => $members) {
            $groups[] = $pending[$key] ?? $members;
        }

        return $groups;
    }

    private function accountName(LegacyOrganization $organization, OrganizationDecision $decision): string
    {
        return $decision->group ?? $organization->displayName;
    }

    private function markConsultantAccount(ImportDecisions $decisions): void
    {
        $decision = $decisions->consultantDecision();

        if (null === $decision || OrganizationTarget::Reuse !== $decision->target || null === $decision->accountId) {
            return;
        }

        $account = $this->decisionsResolver->account($decision->accountId);

        if (!$account instanceof Account || $account->isConsultant()) {
            return;
        }

        if ($this->dryRun) {
            $this->io->writeln(sprintf('Cuenta "%s": se marcaría como consultora', $account->name()));

            return;
        }

        try {
            $this->changeConsultantStatus->__invoke(new ChangeConsultantStatusRequest($decision->accountId, consultant: true), $this->user);

            $this->io->writeln(sprintf('Cuenta "%s" marcada como consultora', $account->name()));
        } catch (\Throwable $exception) {
            $decision->consultant = false;
            $decisions->consultantOrganizationId = null;

            $this->error('Consultora', sprintf('Cuenta "%s": %s', $account->name(), $exception->getMessage()));
        }
    }

    private function propertiesStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions, ImportOptions $options): void
    {
        $this->io->section('Propiedades');

        $rows = [];
        $toCreate = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            if ($decision->usesAccountCalculator($organization) || $decision->isSkipped()) {
                continue;
            }

            $assignment = $decisions->groups->assignment($organization->id);

            try {
                $decision->propertyId = $this->decisionsResolver->property($organization, $decision, $options, $assignment);
            } catch (\InvalidArgumentException) {
                $decision->propertyId = null;
                $decision->createProperty = false;
            }

            $choices = [];

            foreach (null !== $decision->accountId ? $this->decisionsResolver->properties($decision->accountId) : [] as $property) {
                $choices[sprintf('%s (%s)', $property->name(), $property->id())] = $property->id();
            }

            $create = sprintf('Crear propiedad nueva "%s"', $organization->displayName);
            $default = null !== $decision->propertyId ? array_search($decision->propertyId, $choices, false) : false;

            if (false === $default) {
                $default = $decision->createProperty ? $create : self::NO_PROPERTY;
            }

            $answer = $this->io->choice(
                sprintf('Propiedad de "%s" (%s)', $organization->displayName, $organization->slug),
                array_merge(array_keys($choices), [$create, self::NO_PROPERTY]),
                $default,
            );

            $decision->propertyId = $choices[$answer] ?? null;
            $decision->createProperty = $create === $answer;

            if ($decision->createProperty) {
                $toCreate[] = [$organization, $decision];
            }

            $rows[] = [
                $organization->slug,
                $decision->group ?? '-',
                null !== $decision->accountId ? (string) $decision->accountId : 'nueva',
                match (true) {
                    null !== $decision->propertyId => (string) $decision->propertyId,
                    $decision->createProperty => 'se creará',
                    default => 'sin propiedad',
                },
                $assignment->bioscoreHotelId ?? '-',
            ];
        }

        $this->io->table(['Organización', 'Grupo', 'Cuenta', 'Propiedad', 'Id Bioscore'], $rows);

        if ([] === $toCreate) {
            return;
        }

        if (!$this->io->confirm(sprintf('¿Crear las %d propiedades marcadas como "Crear"?', count($toCreate)), true)) {
            foreach ($toCreate as [, $decision]) {
                $decision->createProperty = false;
                $this->count('Propiedades', self::SKIPPED);
            }

            $this->io->warning('Paso omitido: las organizaciones marcadas como "Crear" quedan sin propiedad.');

            return;
        }

        $created = [];

        foreach ($toCreate as [$organization, $decision]) {
            $assignment = $decisions->groups->assignment($organization->id);
            $key = $decision->accountKey . '|' . ($assignment->bioscoreHotelId ?? ImportDecisionsResolver::normalizeName($organization->displayName));

            if (isset($created[$key])) {
                [$propertyId, $slug] = $created[$key];
                $decision->propertyId = $propertyId;
                $decision->createProperty = null === $propertyId;

                $this->io->writeln(sprintf('Propiedad de "%s": misma propiedad que %s', $organization->slug, $slug));
                $this->count('Propiedades', self::REUSED);

                continue;
            }

            $name = trim(preg_replace('/\s+/u', ' ', $organization->displayName) ?? $organization->displayName);

            if ($this->dryRun || null === $decision->accountId) {
                $created[$key] = [null, $organization->slug];
                $this->io->writeln(sprintf('Propiedad "%s": se crearía', $name));
                $this->count('Propiedades', self::CREATED);

                continue;
            }

            try {
                $response = $this->registerProperty->__invoke(
                    new RegisterPropertyRequest(
                        name: $name,
                        accountId: $decision->accountId,
                        info: new PropertyInfo(
                            propertyType: PropertyType::Hotel,
                            address: $assignment?->address,
                            postalCode: $assignment?->postalCode,
                            longitude: $assignment?->longitude,
                            latitude: $assignment?->latitude,
                        ),
                        externalId: null !== $assignment?->bioscoreHotelId
                            ? ExternalId::fromSourceAndId(ExternalSource::Bioscore, $assignment->bioscoreHotelId)
                            : null,
                    ),
                    $this->user,
                );

                $decision->propertyId = $response->propertyId;
                $decision->createProperty = false;
                $created[$key] = [$response->propertyId, $organization->slug];

                $this->io->writeln(sprintf('Propiedad "%s" creada: %s', $name, $response->propertyId));
                $this->count('Propiedades', self::CREATED);
            } catch (\Throwable $exception) {
                $decision->propertyId = null;
                $decision->createProperty = false;

                $this->error('Propiedades', sprintf('Propiedad "%s": %s', $name, $exception->getMessage()));
            }
        }
    }

    private function consultantAssignmentStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $consultant = $decisions->consultantDecision();

        if (null === $consultant || $consultant->isSkipped()) {
            return;
        }

        $this->io->section('Consultora de las cuentas');

        $consultantNames = [];

        foreach ($this->decisionsResolver->consultants() as $account) {
            $consultantNames[(string) $account->id()] = $account->name();
        }

        $rows = [];
        $toAssign = [];
        $seen = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            if ($decision->isSkipped() || $decision->staff || $decision->consultant || isset($seen[$decision->accountKey])) {
                continue;
            }

            $seen[$decision->accountKey] = true;
            $account = null !== $decision->accountId ? $this->decisionsResolver->account($decision->accountId) : null;
            $current = $account?->consultantAccountId();
            $action = $this->consultantAction($account, $consultant->accountId);
            $label = $account instanceof Account
                ? sprintf('%s (%s)', $account->name(), $account->id())
                : sprintf('%s (nueva)', $this->accountName($organization, $decision));

            $rows[] = [
                $label,
                null !== $current ? $consultantNames[(string) $current] ?? (string) $current : '-',
                $action,
            ];

            if ('asignar' === $action || 'se asignaría' === $action) {
                $toAssign[] = [$label, $decision->accountId, $action];
            } elseif ('ya asignada' === $action) {
                $this->count('Consultora', self::REUSED);
            } else {
                $this->count('Consultora', self::SKIPPED);
            }
        }

        $this->io->table(['Cuenta', 'Consultora actual', 'Acción'], $rows);

        if ([] === $toAssign) {
            return;
        }

        if (!$this->io->confirm(sprintf('¿Asignar la consultora a las %d cuentas?', count($toAssign)), true)) {
            $this->io->warning('Paso omitido: no se asigna la consultora a ninguna cuenta.');

            return;
        }

        foreach ($toAssign as [$label, $accountId, $action]) {
            if ('se asignaría' === $action || null === $accountId || null === $consultant->accountId) {
                $this->io->writeln(sprintf('Cuenta %s: se asignaría la consultora', $label));
                $this->count('Consultora', self::CREATED);

                continue;
            }

            try {
                $this->assignConsultant->__invoke(new AssignConsultantRequest($accountId, $consultant->accountId), $this->user);

                $this->io->writeln(sprintf('Consultora asignada a la cuenta %s', $label));
                $this->count('Consultora', self::CREATED);
            } catch (\Throwable $exception) {
                $this->error('Consultora', sprintf('Cuenta %s: %s', $label, $exception->getMessage()));
            }
        }
    }

    private function consultantAction(?Account $account, ?AccountId $consultantAccountId): string
    {
        $current = $account?->consultantAccountId();

        return match (true) {
            null !== $account && $account->isConsultant() => 'es consultora, no se asigna',
            null !== $current && null !== $consultantAccountId && $current->equals($consultantAccountId) => 'ya asignada',
            null !== $current => 'tiene otra consultora, se mantiene',
            $this->dryRun || null === $account || null === $consultantAccountId => 'se asignaría',
            default => 'asignar',
        };
    }

    private function usersStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $this->io->section('Usuarios');

        $rows = [];
        $toCreate = [];

        foreach ($dataset->users as $legacyUser) {
            [$membership, $action, $notApplied] = $this->userPlan($dataset, $decisions, $legacyUser);

            $organization = null !== $membership ? $dataset->organizationById($membership->organizationId) : null;
            $accountId = null !== $organization ? $this->decision($decisions, $organization)->accountId : null;

            $rows[] = [
                $legacyUser->email,
                null !== $organization ? sprintf('%s (%s)', $organization->displayName, null !== $accountId ? $accountId : 'nueva') : '-',
                null !== $membership ? $this->role($membership)->value : '-',
                $this->dryRun && 'crear' === $action ? 'se crearía' : $action,
                implode(', ', $notApplied),
            ];

            if ('crear' === $action && null !== $membership) {
                $toCreate[] = [$legacyUser, $membership, $accountId];
            } elseif ('ya existía' === $action) {
                $this->count('Usuarios', self::REUSED);
            } else {
                $this->count('Usuarios', self::SKIPPED);
            }
        }

        $this->io->table(['Email', 'Cuenta', 'Rol', 'Acción', 'Pertenencias no aplicadas'], $rows);

        if ([] === $toCreate) {
            return;
        }

        if (!$this->io->confirm(sprintf('¿Crear los %d usuarios marcados como "crear"?', count($toCreate)), true)) {
            $this->io->warning('Paso omitido: no se crea ningún usuario.');

            return;
        }

        foreach ($toCreate as [$legacyUser, $membership, $accountId]) {
            if ($this->dryRun || null === $accountId) {
                $this->io->writeln(sprintf('Usuario %s: se crearía', $legacyUser->email));
                $this->count('Usuarios', self::CREATED);

                continue;
            }

            try {
                $response = $this->registerUser->__invoke(
                    new RegisterUserRequest($legacyUser->email, bin2hex(random_bytes(16)), $accountId, welcome: false),
                    $this->user,
                );

                $this->grantRole->__invoke(new GrantRoleRequest($response->userId, $this->role($membership)), $this->user);

                $this->io->writeln(sprintf('Usuario %s creado como %s', $legacyUser->email, $this->role($membership)->value));
                $this->count('Usuarios', self::CREATED);
            } catch (\Throwable $exception) {
                $this->error('Usuarios', sprintf('Usuario %s: %s', $legacyUser->email, $exception->getMessage()));
            }
        }
    }

    /** @return array{0: ?LegacyMembership, 1: string, 2: list<string>} */
    private function userPlan(LegacyBalanceDataset $dataset, ImportDecisions $decisions, LegacyUser $legacyUser): array
    {
        if ($legacyUser->globalAdmin) {
            return [null, 'personal', []];
        }

        $valid = [];

        foreach ($dataset->membershipsOfUser($legacyUser->id) as $membership) {
            $organization = $dataset->organizationById($membership->organizationId);

            if (null === $organization) {
                continue;
            }

            $decision = $this->decision($decisions, $organization);

            if ($decision->staff) {
                return [null, 'personal', []];
            }

            if (!$decision->isSkipped()) {
                $valid[] = $membership;
            }
        }

        if ([] === $valid) {
            return [null, 'sin cuenta, omitido', []];
        }

        $first = array_shift($valid);
        $accountKey = $this->membershipDecision($dataset, $decisions, $first)?->accountKey;
        $notApplied = array_values(array_map(
            static fn(LegacyMembership $membership): string => $dataset->organizationById($membership->organizationId)->slug ?? $membership->organizationId,
            array_filter(
                $valid,
                fn(LegacyMembership $membership): bool => $this->membershipDecision($dataset, $decisions, $membership)?->accountKey !== $accountKey,
            ),
        ));

        if ($this->userRepository->findByUsername($legacyUser->email) instanceof User) {
            return [$first, 'ya existía', $notApplied];
        }

        return [$first, 'crear', $notApplied];
    }

    private function membershipDecision(LegacyBalanceDataset $dataset, ImportDecisions $decisions, LegacyMembership $membership): ?OrganizationDecision
    {
        $organization = $dataset->organizationById($membership->organizationId);

        return null !== $organization ? $this->decision($decisions, $organization) : null;
    }

    private function role(LegacyMembership $membership): Role
    {
        return $membership->isAdmin() ? Role::Owner : Role::User;
    }

    private function calculationsStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $this->io->section('Medios de transporte y cálculos');

        $mobilities = [];

        foreach ($this->decisionsResolver->mobilities() as $mobility) {
            $mobilities[sprintf('%s (%s)', $mobility->name(), $mobility->id())] = $mobility->id();
        }

        foreach ($this->decisionsResolver->transports($dataset, $decisions) as $transport) {
            $preselected = $decisions->mobilities[$transport] ?? null;
            $default = null !== $preselected ? array_search($preselected, $mobilities, false) : false;

            $answer = $this->io->choice(
                sprintf('Medio de transporte para los cálculos "%s"', $transport),
                array_keys($mobilities),
                false !== $default ? $default : null,
            );

            $decisions->mobilities[$transport] = $mobilities[$answer];
        }

        $rows = [];
        $plans = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);
            $calculations = $dataset->calculationsOfOrganization($organization->slug);

            if ([] === $calculations || $this->isDuplicated($dataset, $organization)) {
                continue;
            }

            if ($decision->isSkipped()) {
                $rows[] = [$organization->slug, '-', 0, 0, sprintf('%d (omitida)', count($calculations))];
                $this->count('Cálculos', self::SKIPPED, count($calculations));

                continue;
            }

            if (!$decision->usesAccountCalculator($organization) && !$decision->hasProperty()) {
                $rows[] = [$organization->slug, '-', 0, 0, sprintf('%d (sin propiedad)', count($calculations))];
                $this->count('Cálculos', self::SKIPPED, count($calculations));

                continue;
            }

            $link = $this->existingLink($organization, $decision);
            $existing = null !== $link ? $this->existingCalculations($link) : [];
            $new = array_values(array_filter(
                $calculations,
                fn(LegacyCalculation $calculation): bool => !$this->legacyCalculationMapper->isDuplicated($calculation, $existing),
            ));

            $rows[] = [
                $organization->slug,
                $this->linkLabel($organization, $decision),
                count($new),
                count($calculations) - count($new),
                0,
            ];

            $this->count('Cálculos', self::REUSED, count($calculations) - count($new));
            $plans[] = [$organization, $decision, $new];
        }

        $withoutReferral = count(array_filter(
            $dataset->calculations,
            static fn(LegacyCalculation $calculation): bool => null === $calculation->referralSlug,
        ));

        if ($withoutReferral > 0) {
            $rows[] = ['(sin referral)', '-', 0, 0, sprintf('%d (sin referral)', $withoutReferral)];
            $this->count('Cálculos', self::SKIPPED, $withoutReferral);
        }

        $this->io->table(['Organización', 'Enlace destino', 'A importar', 'Duplicados', 'Omitidos'], $rows);

        $total = array_sum(array_map(static fn(array $plan): int => count($plan[2]), $plans));

        if (0 === $total) {
            return;
        }

        if (!$this->io->confirm(sprintf('¿Importar los %d cálculos?', $total), true)) {
            $this->io->warning('Paso omitido: no se importa ningún cálculo.');

            return;
        }

        foreach ($plans as [$organization, $decision, $calculations]) {
            if ([] === $calculations) {
                continue;
            }

            if ($this->dryRun || null === $decision->accountId) {
                $this->io->writeln(sprintf('%s: se importarían %d cálculos', $organization->slug, count($calculations)));
                $this->count('Cálculos', self::CREATED, count($calculations));

                continue;
            }

            $link = $this->registerLink($organization, $decision);
            $imported = 0;

            foreach ($calculations as $calculation) {
                try {
                    $this->importTripCalculation->__invoke($this->legacyCalculationMapper->request($link->id(), $calculation, $decisions->mobilities), $this->user);
                    ++$imported;
                    $this->count('Cálculos', self::CREATED);
                } catch (\Throwable $exception) {
                    $this->error('Cálculos', sprintf('Cálculo %s: %s', $calculation->id, $exception->getMessage()));
                }
            }

            $this->io->writeln(sprintf('%s: %d cálculos importados', $organization->slug, $imported));
        }
    }

    /** @return list<TripCalculation> */
    private function existingCalculations(TripCalculationLink $link): array
    {
        return $this->tripCalculationRepository->findByFilter(
            new TripCalculationFilter(tripCalculationLinkId: $link->id(), limit: 0),
        );
    }

    private function contributionsStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $this->io->section('Proyectos y aportaciones');

        $projects = [];

        foreach ($this->decisionsResolver->projects() as $project) {
            $projects[sprintf('%s (%s, %s €/t)', $project->name(), $project->id(), $project->pricePerTonne() ?? '-')] = $project->id();
        }

        foreach ($this->decisionsResolver->referencedEcoProjectIds($dataset, $decisions) as $ecoProjectId) {
            $preselected = $decisions->projects[$ecoProjectId] ?? null;
            $default = null !== $preselected ? array_search($preselected, $projects, false) : false;

            $answer = $this->io->choice(
                sprintf('Proyecto equivalente a "%s" (%s)', $dataset->ecoProjectById($ecoProjectId)->displayName ?? $ecoProjectId, $ecoProjectId),
                array_merge(array_keys($projects), [self::NO_PROJECT]),
                false !== $default ? $default : self::NO_PROJECT,
            );

            $decisions->projects[$ecoProjectId] = $projects[$answer] ?? null;
        }

        $rows = [];
        $toImport = [];

        foreach ($dataset->donations as $donation) {
            $organization = $dataset->organizationById($donation->organizationId);

            if (null === $organization || $this->decision($decisions, $organization)->isSkipped()) {
                continue;
            }

            $accountId = $this->decision($decisions, $organization)->accountId;
            $projectId = $decisions->projects[$donation->ecoProjectId] ?? null;
            $project = null !== $projectId ? $this->projectRepository->find($projectId) : null;
            $pricePerTonne = $project?->pricePerTonne();
            $action = $this->contributionAction($donation, $project, $accountId);

            $rows[] = [
                $donation->transactionUuid,
                null !== $accountId ? (string) $accountId : 'nueva',
                sprintf('%s → %s', $dataset->ecoProjectById($donation->ecoProjectId)->displayName ?? $donation->ecoProjectId, $project?->name() ?? '-'),
                number_format($donation->amount, 2, ',', '.'),
                null !== $pricePerTonne && $pricePerTonne > 0.0 ? (string) round($donation->amount / $pricePerTonne, 6) : '-',
                $action,
            ];

            if ('importar' === $action && null !== $accountId && null !== $projectId) {
                $toImport[] = [$donation, $accountId, $projectId];
            } elseif ('ya existía' === $action) {
                $this->count('Aportaciones', self::REUSED);
            } elseif ('se crearía' === $action) {
                $this->count('Aportaciones', self::CREATED);
            } else {
                $this->count('Aportaciones', self::SKIPPED);
            }
        }

        $this->io->table(['Transacción', 'Cuenta', 'Proyecto antiguo → proyecto', 'Importe (€)', 'Toneladas', 'Acción'], $rows);

        if ([] === $toImport) {
            return;
        }

        if (!$this->io->confirm(sprintf('¿Importar las %d aportaciones?', count($toImport)), true)) {
            $this->io->warning('Paso omitido: no se importa ninguna aportación.');

            return;
        }

        foreach ($toImport as [$donation, $accountId, $projectId]) {
            try {
                $this->importPaidProjectContribution->__invoke(
                    new ImportPaidProjectContributionRequest(
                        projectContributionId: new ProjectContributionId($donation->transactionUuid),
                        accountId: $accountId,
                        projectId: $projectId,
                        amount: $donation->amount,
                        contributorName: $donation->donerName,
                        contributorEmail: $donation->donerEmail,
                        paidAt: $donation->createdAt,
                    ),
                    $this->user,
                );

                $this->io->writeln(sprintf('Aportación %s importada', $donation->transactionUuid));
                $this->count('Aportaciones', self::CREATED);
            } catch (DuplicatedProjectContributionException) {
                $this->io->writeln(sprintf('Aportación %s: ya existía', $donation->transactionUuid));
                $this->count('Aportaciones', self::REUSED);
            } catch (\Throwable $exception) {
                $this->error('Aportaciones', sprintf('Aportación %s: %s', $donation->transactionUuid, $exception->getMessage()));
            }
        }
    }

    private function contributionAction(LegacyDonation $donation, ?Project $project, ?AccountId $accountId): string
    {
        if (null === $project) {
            return 'sin equivalente';
        }

        $pricePerTonne = $project->pricePerTonne();

        if (null === $pricePerTonne || $pricePerTonne <= 0.0) {
            return 'sin precio';
        }

        if ($this->projectContributionRepository->find(new ProjectContributionId($donation->transactionUuid)) instanceof ProjectContribution) {
            return 'ya existía';
        }

        return $this->dryRun || null === $accountId ? 'se crearía' : 'importar';
    }

    private function redirectsStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions, string $directory): void
    {
        $this->io->section('Redirecciones');

        $rows = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            if ($decision->isSkipped()) {
                $rows[] = [$organization->slug, $organization->type, 'omitida'];

                continue;
            }

            if ($this->isDuplicated($dataset, $organization)) {
                $rows[] = [$organization->slug, $organization->type, sprintf('duplicada de %s', $dataset->organizationBySlug($organization->slug)->id ?? '-')];

                continue;
            }

            if (!$decision->usesAccountCalculator($organization) && !$decision->hasProperty()) {
                $rows[] = [$organization->slug, $organization->type, sprintf('{{PROPERTY_TRIP_URL:%s}}', $organization->slug)];

                continue;
            }

            $link = $this->existingLink($organization, $decision);
            $rows[] = [$organization->slug, $organization->type, null !== $link ? $this->linkUrl($link) : $this->linkLabel($organization, $decision) . ' (se creará)'];
        }

        $this->io->table(['Organización', 'Tipo', 'URL o marcador'], $rows);

        $path = $directory . '/' . self::REDIRECTS_FILE;

        if (!$this->io->confirm(sprintf('¿Escribir las redirecciones en %s?', $path), true)) {
            $this->io->warning('Paso omitido: no se escribe el fichero de redirecciones.');

            return;
        }

        $rules = [];

        foreach ($dataset->organizations as $organization) {
            if (!$this->isDuplicated($dataset, $organization)) {
                $rules[] = $this->redirectRule($organization, $this->decision($decisions, $organization));
            }
        }

        $this->redirectRulesWriter->write($path, $decisions->legacyUrl, $rules);

        $this->io->writeln(sprintf('Fichero de redirecciones escrito en %s', $path));
        $this->count('Redirecciones', self::CREATED, count(array_filter($rules, static fn(RedirectRule $rule): bool => !$rule->skipped)));
        $this->count('Redirecciones', self::SKIPPED, count(array_filter($rules, static fn(RedirectRule $rule): bool => $rule->skipped)));
    }

    private function redirectRule(LegacyOrganization $organization, OrganizationDecision $decision): RedirectRule
    {
        $account = null !== $decision->accountId ? (string) $decision->accountId : 'nueva';

        if ($decision->isSkipped()) {
            return new RedirectRule($organization->slug, $organization->type, null, 'omitida', skipped: true);
        }

        if (!$decision->usesAccountCalculator($organization) && !$decision->hasProperty()) {
            return new RedirectRule(
                $organization->slug,
                $organization->type,
                null,
                sprintf('sin propiedad en la cuenta %s: sustituir el marcador por la URL de la calculadora de la propiedad', $account),
            );
        }

        $link = $this->existingLink($organization, $decision);

        if (null === $link && !$this->dryRun && null !== $decision->accountId) {
            $link = $this->registerLink($organization, $decision);
        }

        $comment = $decision->usesAccountCalculator($organization)
            ? sprintf('calculadora de viajes de la cuenta %s', $account)
            : sprintf('calculadora de viajes de la propiedad %s', $decision->propertyId);

        return new RedirectRule(
            $organization->slug,
            $organization->type,
            null !== $link ? $this->linkUrl($link) : self::DRY_RUN_MARKER,
            $comment,
        );
    }

    private function existingLink(LegacyOrganization $organization, OrganizationDecision $decision): ?TripCalculationLink
    {
        if ($decision->usesAccountCalculator($organization)) {
            return null !== $decision->accountId ? $this->tripCalculationLinkRepository->findForAccount($decision->accountId) : null;
        }

        return null !== $decision->propertyId ? $this->tripCalculationLinkRepository->findByPropertyId($decision->propertyId) : null;
    }

    private function registerLink(LegacyOrganization $organization, OrganizationDecision $decision): TripCalculationLink
    {
        if ($decision->usesAccountCalculator($organization)) {
            return $this->registerAccountTripCalculationLink->__invoke(
                new RegisterAccountTripCalculationLinkRequest($decision->accountId),
                $this->user,
            )->tripCalculationLink;
        }

        if (!$decision->propertyId instanceof PropertyId) {
            throw new \LogicException(sprintf('La organización "%s" no tiene propiedad.', $organization->slug));
        }

        return $this->registerTripCalculationLink->__invoke(
            new RegisterTripCalculationLinkRequest($decision->propertyId),
            $this->user,
        )->tripCalculationLink;
    }

    private function linkLabel(LegacyOrganization $organization, OrganizationDecision $decision): string
    {
        return $decision->usesAccountCalculator($organization)
            ? sprintf('cuenta %s', null !== $decision->accountId ? $decision->accountId : 'nueva')
            : sprintf('propiedad %s', null !== $decision->propertyId ? $decision->propertyId : 'nueva');
    }

    private function linkUrl(TripCalculationLink $link): string
    {
        return $this->urlGenerator->generate(
            PublicTripRoutes::for($link)->name(),
            ['token' => $link->token()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    private function decision(ImportDecisions $decisions, LegacyOrganization $organization): OrganizationDecision
    {
        return $decisions->organization($organization->id)
            ?? throw new \LogicException(sprintf('Sin decisión para la organización "%s".', $organization->slug));
    }

    private function isDuplicated(LegacyBalanceDataset $dataset, LegacyOrganization $organization): bool
    {
        return $dataset->organizationBySlug($organization->slug)?->id !== $organization->id;
    }

    private function targetLabel(OrganizationTarget $target): string
    {
        return match ($target) {
            OrganizationTarget::Skip => 'omitir',
            OrganizationTarget::Create => 'crear',
            OrganizationTarget::Reuse => 'reutilizar',
        };
    }

    private function count(string $type, string $column, int $amount = 1): void
    {
        $this->summary[$type][$column] = ($this->summary[$type][$column] ?? 0) + $amount;
    }

    private function error(string $type, string $message): void
    {
        $this->io->warning($message);
        $this->count($type, self::ERRORS);
    }

    private function showSummary(): void
    {
        $this->io->section($this->dryRun ? 'Resumen (simulación)' : 'Resumen');

        $rows = [];

        foreach (['Cuentas', 'Grupos sin cuenta', 'Propiedades', 'Consultora', 'Usuarios', 'Cálculos', 'Aportaciones', 'Redirecciones'] as $type) {
            $rows[] = [
                $type,
                $this->summary[$type][self::CREATED] ?? 0,
                $this->summary[$type][self::REUSED] ?? 0,
                $this->summary[$type][self::SKIPPED] ?? 0,
                $this->summary[$type][self::ERRORS] ?? 0,
            ];
        }

        $this->io->table(['Tipo', 'Creados', 'Reutilizados', 'Omitidos', 'Errores'], $rows);
        $this->io->success('Importación terminada.');
    }
}
