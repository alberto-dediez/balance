<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Console\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Register\RegisterDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Register\RegisterDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'property:distributor:assign',
    description: 'Adhiere a una cuenta distribuidora las propiedades de un fichero CSV',
)]
final class AssignDistributorFromFileCommand extends Command
{
    private const string ASSIGN = 'asignar';
    private const string ALREADY_ASSIGNED = 'ya asignada';
    private const string ALREADY_ASSIGNED_BY_ACCOUNT = 'ya adherida por cuenta';
    private const string OWN_PROPERTY = 'propia de la distribuidora';
    private const string NOT_FOUND = 'no encontrada';
    private const string AMBIGUOUS = 'varias coincidencias';
    private const string WITHOUT_ACCOUNT = 'sin cuenta';

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly DistributorAssignmentRepository $assignmentRepository,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly RegisterDistributorAssignmentService $registerAssignmentService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Fichero CSV con cabecera y una propiedad por fila')
            ->addOption('distributor', null, InputOption::VALUE_REQUIRED, 'Cuenta distribuidora (AccountId)')
            ->addOption('delimiter', null, InputOption::VALUE_REQUIRED, 'Separador de columnas', ';')
            ->addOption('property-column', null, InputOption::VALUE_REQUIRED, 'Columna con el id de la propiedad (PropertyId)', 'property_id')
            ->addOption('bioscore-column', null, InputOption::VALUE_REQUIRED, 'Columna con el id del hotel en Bioscore', 'bioscore_hotel_id')
            ->addOption('name-column', null, InputOption::VALUE_REQUIRED, 'Columna con el nombre de la propiedad', 'name')
            ->addOption('account-column', null, InputOption::VALUE_REQUIRED, 'Columna con la cuenta de la propiedad (id o nombre)', 'account')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Muestra lo que se haría sin escribir en base de datos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Adhesión de propiedades a un distribuidor');

        if ($dryRun) {
            $io->note('Simulación (--dry-run): no se escribe nada en base de datos.');
        }

        $distributorOption = $input->getOption('distributor');

        if (!is_string($distributorOption) || '' === trim($distributorOption)) {
            $io->error('Indica la cuenta distribuidora con --distributor=<accountId>.');

            return Command::FAILURE;
        }

        $distributorAccountId = new AccountId(trim($distributorOption));
        $distributor = $this->accountRepository->find($distributorAccountId);

        if (!$distributor instanceof Account || !$this->distributorPolicy->isActiveDistributor($distributorAccountId)) {
            $io->error(sprintf('La cuenta "%s" no es una cuenta distribuidora activa.', $distributorAccountId->id()));

            return Command::FAILURE;
        }

        $io->text(sprintf('Distribuidora: <info>%s</info> (%s)', $distributor->name(), $distributorAccountId->id()));

        try {
            $rows = $this->readRows((string) $input->getArgument('file'), (string) $input->getOption('delimiter'));
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $columns = [
            'property' => (string) $input->getOption('property-column'),
            'bioscore' => (string) $input->getOption('bioscore-column'),
            'name' => (string) $input->getOption('name-column'),
            'account' => (string) $input->getOption('account-column'),
        ];

        $matches = $this->matches($rows, $columns, $distributorAccountId);

        $io->section('Propiedades');
        $io->table(
            ['Línea', 'Fila', 'Propiedad', 'Encontrada por', 'Acción'],
            array_map(static fn(array $match): array => [
                $match['line'],
                $match['label'],
                $match['property'] instanceof Property ? sprintf('%s (%s)', $match['property']->name(), $match['property']->id()->id()) : '-',
                $match['method'],
                $match['status'],
            ], $matches),
        );

        $toAssign = array_values(array_filter($matches, static fn(array $match): bool => self::ASSIGN === $match['status']));
        $unresolved = array_values(array_filter($matches, static fn(array $match): bool => in_array($match['status'], [self::NOT_FOUND, self::AMBIGUOUS], true)));

        if ([] !== $unresolved) {
            $io->warning(sprintf('%d filas sin propiedad o con varias coincidencias: revísalas o añade la columna "%s".', count($unresolved), $columns['property']));
        }

        if ([] === $toAssign) {
            $io->success('No hay propiedades nuevas que adherir.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->success(sprintf('Se adherirían %d propiedades.', count($toAssign)));

            return Command::SUCCESS;
        }

        if (!$io->confirm(sprintf('¿Adherir %d propiedades a "%s"?', count($toAssign), $distributor->name()), true)) {
            $io->text('Cancelado.');

            return Command::SUCCESS;
        }

        $user = UserMother::systemUser();
        $assigned = 0;
        $failed = 0;

        foreach ($toAssign as $match) {
            $property = $match['property'];

            if (!$property instanceof Property) {
                continue;
            }

            try {
                ($this->registerAssignmentService)(
                    new RegisterDistributorAssignmentRequest(distributorAccountId: $distributorAccountId, propertyId: $property->id()),
                    $user,
                );
                ++$assigned;
            } catch (\RuntimeException|\InvalidArgumentException $exception) {
                ++$failed;
                $io->error(sprintf('Línea %d, %s: %s', $match['line'], $property->name(), $exception->getMessage()));
            }
        }

        $io->success(sprintf('Propiedades adheridas: %d. Errores: %d.', $assigned, $failed));

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }

    /** @return list<array{line: int, values: array<string, string>}> */
    private function readRows(string $path, string $delimiter): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('No se puede leer el fichero "%s".', $path));
        }

        $file = fopen($path, 'r');

        if (false === $file) {
            throw new \RuntimeException(sprintf('No se puede abrir el fichero "%s".', $path));
        }

        $header = fgetcsv($file, 0, '' !== $delimiter ? $delimiter : ';', '"', '');

        if (!is_array($header)) {
            fclose($file);

            throw new \RuntimeException(sprintf('El fichero "%s" no tiene cabecera.', $path));
        }

        $header = array_map(static fn(?string $column): string => trim((string) $column, " \t\n\r\0\x0B\u{FEFF}"), $header);
        $rows = [];
        $line = 1;

        while (false !== ($values = fgetcsv($file, 0, '' !== $delimiter ? $delimiter : ';', '"', ''))) {
            ++$line;

            if ([null] === $values) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = trim((string) ($values[$index] ?? ''));
            }

            $rows[] = ['line' => $line, 'values' => $row];
        }

        fclose($file);

        return $rows;
    }

    /**
     * @param list<array{line: int, values: array<string, string>}>                    $rows
     * @param array{property: string, bioscore: string, name: string, account: string} $columns
     *
     * @return list<array{line: int, label: string, property: ?Property, method: string, status: string}>
     */
    private function matches(array $rows, array $columns, AccountId $distributorAccountId): array
    {
        $matches = [];
        $seen = [];

        foreach ($rows as $row) {
            $values = $row['values'];
            $label = $this->label($values, $columns);
            [$properties, $method] = $this->resolve($values, $columns);

            if ([] === $properties) {
                $matches[] = ['line' => $row['line'], 'label' => $label, 'property' => null, 'method' => $method, 'status' => self::NOT_FOUND];

                continue;
            }

            if (count($properties) > 1) {
                $matches[] = ['line' => $row['line'], 'label' => $label, 'property' => null, 'method' => $method, 'status' => self::AMBIGUOUS];

                continue;
            }

            $property = $properties[0];
            $propertyId = $property->id()->id();

            if (isset($seen[$propertyId])) {
                $matches[] = ['line' => $row['line'], 'label' => $label, 'property' => $property, 'method' => $method, 'status' => sprintf('repetida (línea %d)', $seen[$propertyId])];

                continue;
            }

            $seen[$propertyId] = $row['line'];
            $matches[] = ['line' => $row['line'], 'label' => $label, 'property' => $property, 'method' => $method, 'status' => $this->status($property, $distributorAccountId)];
        }

        return $matches;
    }

    /**
     * @param array<string, string>                                                    $values
     * @param array{property: string, bioscore: string, name: string, account: string} $columns
     *
     * @return array{0: list<Property>, 1: string}
     */
    private function resolve(array $values, array $columns): array
    {
        $propertyId = $values[$columns['property']] ?? '';

        if ('' !== $propertyId) {
            $property = $this->propertyRepository->find(new PropertyId($propertyId));

            return [$property instanceof Property && !$property->isRemoved() ? [$property] : [], 'id'];
        }

        $bioscoreId = $values[$columns['bioscore']] ?? '';

        if ('' !== $bioscoreId) {
            $property = $this->propertyRepository->findBySourceAndExternalId(
                ExternalSource::Bioscore,
                ExternalId::fromSourceAndId(ExternalSource::Bioscore, $bioscoreId),
            );

            if ($property instanceof Property) {
                return [[$property], 'bioscore'];
            }
        }

        $name = $values[$columns['name']] ?? '';

        if ('' === $name) {
            return [[], '' !== $bioscoreId ? 'bioscore' : '-'];
        }

        $accountValue = $values[$columns['account']] ?? '';

        if ('' === $accountValue) {
            return [$this->propertiesNamed($name, new PropertyFilter(name: $name, limit: 0)), 'nombre'];
        }

        $account = $this->account($accountValue);

        if (!$account instanceof Account) {
            return [[], 'cuenta no encontrada'];
        }

        return [$this->propertiesNamed($name, new PropertyFilter(accountId: $account->id(), limit: 0)), 'cuenta y nombre'];
    }

    /** @return list<Property> */
    private function propertiesNamed(string $name, PropertyFilter $filter): array
    {
        $key = self::normalizeName($name);

        return array_values(array_filter(
            $this->propertyRepository->findByFilter($filter),
            static fn(Property $property): bool => self::normalizeName($property->name()) === $key,
        ));
    }

    private function account(string $value): ?Account
    {
        $account = $this->accountRepository->find(new AccountId($value));

        if ($account instanceof Account && !$account->isRemoved()) {
            return $account;
        }

        $account = $this->accountRepository->findByName($value);

        return $account instanceof Account && !$account->isRemoved() ? $account : null;
    }

    private function status(Property $property, AccountId $distributorAccountId): string
    {
        $accountId = $property->accountId();

        if ($accountId instanceof AccountId && $accountId->equals($distributorAccountId)) {
            return self::OWN_PROPERTY;
        }

        if ($this->assignmentRepository->findForProperty($distributorAccountId, $property->id()) instanceof DistributorAssignment) {
            return self::ALREADY_ASSIGNED;
        }

        if ($accountId instanceof AccountId && $this->assignmentRepository->findForAccount($distributorAccountId, $accountId) instanceof DistributorAssignment) {
            return self::ALREADY_ASSIGNED_BY_ACCOUNT;
        }

        return $accountId instanceof AccountId ? self::ASSIGN : self::WITHOUT_ACCOUNT;
    }

    /**
     * @param array<string, string>                                                    $values
     * @param array{property: string, bioscore: string, name: string, account: string} $columns
     */
    private function label(array $values, array $columns): string
    {
        $parts = array_filter([
            $values[$columns['name']] ?? '',
            $values[$columns['account']] ?? '',
        ], static fn(string $part): bool => '' !== $part);

        if ([] !== $parts) {
            return implode(' · ', $parts);
        }

        foreach ([$columns['property'], $columns['bioscore']] as $column) {
            if ('' !== ($values[$column] ?? '')) {
                return $values[$column];
            }
        }

        return '-';
    }

    private static function normalizeName(string $name): string
    {
        $normalized = \Normalizer::normalize(mb_strtolower($name), \Normalizer::FORM_D);
        $normalized = preg_replace('/\p{Mn}+/u', '', false !== $normalized ? $normalized : $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
