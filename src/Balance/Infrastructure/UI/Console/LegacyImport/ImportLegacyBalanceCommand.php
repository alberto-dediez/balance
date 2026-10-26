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
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
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

    private const string DRY_RUN_MARKER = '{{DRY_RUN}}';

    private const string REDIRECTS_FILE = 'redirects.txt';

    private const string CREATED = 'creados';

    private const string REUSED = 'reutilizados';

    private const string SKIPPED = 'omitidos';

    private const string ERRORS = 'errores';

    private SymfonyStyle $io;

    private User $user;

    private bool $dryRun = false;

    /** @var array<string, array<string, int>> */
    private array $summary = [];

    public function __construct(
        private readonly LegacyBalanceDatasetReader $datasetReader,
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
            ->addOption('staff', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Organización cuyos miembros son personal de la plataforma (slug)')
            ->addOption('account', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Cuenta existente de una organización (slug=accountId)')
            ->addOption('property', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Propiedad de una organización (slug=propertyId)')
            ->addOption('project', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Proyecto equivalente de un proyecto antiguo (oldEcoProjectId=projectId)')
            ->addOption('mobility-plane', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en avión (MobilityId)')
            ->addOption('mobility-car', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en coche (MobilityId)')
            ->addOption('mobility-train', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en tren (MobilityId)')
            ->addOption('mobility-bus', null, InputOption::VALUE_REQUIRED, 'Medio de transporte para los cálculos en autobús (MobilityId)')
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

        $this->showTotals($dataset);

        $legacyUrl = $this->legacyUrl($input);

        if (null === $legacyUrl) {
            return Command::FAILURE;
        }

        try {
            $options = $this->options($input);
            $decisions = $this->decisionsResolver->resolve($dataset, $options, $legacyUrl, $input->isInteractive());
        } catch (\InvalidArgumentException $exception) {
            $this->io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->reportOrphanMemberships($dataset);

        try {
            $this->organizationsStep($dataset, $decisions);
            $this->propertiesStep($dataset, $decisions, $options);
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

    private function showTotals(LegacyBalanceDataset $dataset): void
    {
        $this->io->table(
            ['Fichero', 'Registros'],
            [
                ['Organization.json', count($dataset->organizations)],
                ['User.json', count($dataset->users)],
                ['OrganizationMember.json', count($dataset->memberships)],
                ['CalculatedEmissions.json', count($dataset->calculations)],
                ['Donation.json', count($dataset->donations)],
                ['EcoProject.json', count($dataset->ecoProjects)],
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

    private function organizationsStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions): void
    {
        $this->io->section('Organizaciones y cuentas');

        $accounts = [];

        foreach ($this->decisionsResolver->accounts() as $account) {
            $accounts[sprintf('%s (%s)', $account->name(), $account->id())] = $account->id();
        }

        $rows = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);
            $create = sprintf('Crear cuenta nueva "%s"', $organization->displayName);

            $default = match ($decision->target) {
                OrganizationTarget::Skip => self::SKIP,
                OrganizationTarget::Create => $create,
                OrganizationTarget::Reuse => (string) array_search($decision->accountId, $accounts, false),
            };

            $answer = $this->io->choice(
                sprintf('Destino de la organización "%s" (%s, %s)', $organization->displayName, $organization->slug, $organization->type),
                array_merge([self::SKIP, $create], array_keys($accounts)),
                '' !== $default ? $default : $create,
            );

            $decision->target = match ($answer) {
                self::SKIP => OrganizationTarget::Skip,
                $create => OrganizationTarget::Create,
                default => OrganizationTarget::Reuse,
            };
            $decision->accountId = OrganizationTarget::Reuse === $decision->target ? $accounts[$answer] : null;
            $decision->staff = $this->io->confirm(
                sprintf('¿Los miembros de "%s" son personal de la plataforma y no deben importarse?', $organization->slug),
                $decision->staff,
            );

            $rows[] = [
                $organization->slug,
                $organization->type,
                $this->targetLabel($decision->target),
                null !== $decision->accountId ? (string) $decision->accountId : '-',
                $decision->staff ? 'sí' : 'no',
            ];
        }

        $this->io->table(['Organización', 'Tipo', 'Destino', 'Cuenta', 'Personal'], $rows);

        $toCreate = array_filter(
            $dataset->organizations,
            fn(LegacyOrganization $organization): bool => OrganizationTarget::Create === $this->decision($decisions, $organization)->target,
        );

        foreach ($dataset->organizations as $organization) {
            $target = $this->decision($decisions, $organization)->target;

            if (OrganizationTarget::Reuse === $target) {
                $this->count('Cuentas', self::REUSED);
            }

            if (OrganizationTarget::Skip === $target) {
                $this->count('Cuentas', self::SKIPPED);
            }
        }

        if ([] === $toCreate) {
            return;
        }

        if (!$this->io->confirm('¿Crear las cuentas marcadas como "Crear"?', true)) {
            foreach ($toCreate as $organization) {
                $this->decision($decisions, $organization)->target = OrganizationTarget::Skip;
                $this->count('Cuentas', self::SKIPPED);
            }

            $this->io->warning('Paso omitido: las organizaciones marcadas como "Crear" se omiten en el resto de pasos.');

            return;
        }

        foreach ($toCreate as $organization) {
            if ($this->dryRun) {
                $this->io->writeln(sprintf('Cuenta "%s": se crearía', $organization->displayName));
                $this->count('Cuentas', self::CREATED);

                continue;
            }

            try {
                $response = $this->registerAccount->__invoke(
                    new RegisterAccountRequest(name: $organization->displayName, active: $organization->active),
                    $this->user,
                );

                $this->decision($decisions, $organization)->accountId = $response->accountId;
                $this->io->writeln(sprintf('Cuenta "%s" creada: %s', $organization->displayName, $response->accountId));
                $this->count('Cuentas', self::CREATED);
            } catch (\Throwable $exception) {
                $this->decision($decisions, $organization)->target = OrganizationTarget::Skip;
                $this->error('Cuentas', sprintf('Cuenta "%s": %s', $organization->displayName, $exception->getMessage()));
            }
        }
    }

    private function propertiesStep(LegacyBalanceDataset $dataset, ImportDecisions $decisions, ImportOptions $options): void
    {
        $this->io->section('Propiedades');

        $rows = [];

        foreach ($dataset->organizations as $organization) {
            $decision = $this->decision($decisions, $organization);

            if ($organization->isOta() || $decision->isSkipped()) {
                continue;
            }

            $decision->propertyId = null;
            $properties = null !== $decision->accountId ? $this->decisionsResolver->properties($decision->accountId) : [];

            if ([] !== $properties) {
                $choices = [];

                foreach ($properties as $property) {
                    $choices[sprintf('%s (%s)', $property->name(), $property->id())] = $property->id();
                }

                try {
                    $preselected = $this->decisionsResolver->property($organization, $decision, $options);
                } catch (\InvalidArgumentException) {
                    $preselected = null;
                }

                $default = null !== $preselected ? array_search($preselected, $choices, false) : false;

                $answer = $this->io->choice(
                    sprintf('Propiedad de "%s" (%s)', $organization->displayName, $organization->slug),
                    array_merge(array_keys($choices), [self::NO_PROPERTY]),
                    false !== $default ? $default : self::NO_PROPERTY,
                );

                $decision->propertyId = $choices[$answer] ?? null;
            }

            $rows[] = [
                $organization->slug,
                null !== $decision->accountId ? (string) $decision->accountId : 'nueva',
                null !== $decision->propertyId ? (string) $decision->propertyId : 'sin propiedad',
            ];
        }

        $this->io->table(['Organización', 'Cuenta', 'Propiedad'], $rows);
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
        $notApplied = array_map(
            static fn(LegacyMembership $membership): string => $dataset->organizationById($membership->organizationId)->slug ?? $membership->organizationId,
            $valid,
        );

        if ($this->userRepository->findByUsername($legacyUser->email) instanceof User) {
            return [$first, 'ya existía', $notApplied];
        }

        return [$first, 'crear', $notApplied];
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

            if ([] === $calculations) {
                continue;
            }

            if ($decision->isSkipped()) {
                $rows[] = [$organization->slug, '-', 0, 0, sprintf('%d (omitida)', count($calculations))];
                $this->count('Cálculos', self::SKIPPED, count($calculations));

                continue;
            }

            if (!$organization->isOta() && null === $decision->propertyId) {
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

            if (!$organization->isOta() && null === $decision->propertyId) {
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
            $rules[] = $this->redirectRule($organization, $this->decision($decisions, $organization));
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

        if (!$organization->isOta() && null === $decision->propertyId) {
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

        $comment = $organization->isOta()
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
        if ($organization->isOta()) {
            return null !== $decision->accountId ? $this->tripCalculationLinkRepository->findForAccount($decision->accountId) : null;
        }

        return null !== $decision->propertyId ? $this->tripCalculationLinkRepository->findByPropertyId($decision->propertyId) : null;
    }

    private function registerLink(LegacyOrganization $organization, OrganizationDecision $decision): TripCalculationLink
    {
        if ($organization->isOta()) {
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
        return $organization->isOta()
            ? sprintf('cuenta %s', null !== $decision->accountId ? $decision->accountId : 'nueva')
            : sprintf('propiedad %s', $decision->propertyId);
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
        return $decisions->organization($organization->slug)
            ?? throw new \LogicException(sprintf('Sin decisión para la organización "%s".', $organization->slug));
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

        foreach (['Cuentas', 'Usuarios', 'Cálculos', 'Aportaciones', 'Redirecciones'] as $type) {
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
