<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Console\Property;

use App\Property\Application\Brand\Import\BrandRequest;
use App\Property\Application\Brand\Import\ImportBrandsRequest;
use App\Property\Application\Brand\Import\ImportBrandsService;
use App\Property\Application\Category\Import\CategoryRequest;
use App\Property\Application\Category\Import\ImportCategoriesRequest;
use App\Property\Application\Category\Import\ImportCategoriesService;
use App\Property\Application\Country\Import\CountryRequest;
use App\Property\Application\Country\Import\ImportCountriesRequest;
use App\Property\Application\Country\Import\ImportCountriesService;
use App\Property\Application\Destination\Import\DestinationRequest;
use App\Property\Application\Destination\Import\ImportDestinationsRequest;
use App\Property\Application\Destination\Import\ImportDestinationsService;
use App\Property\Application\Property\Import\ExternalIdExtractor;
use App\Property\Application\Property\Import\Field;
use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Application\Property\Import\PropertyInfoExtractor;
use App\Property\Application\Property\Match\MatchPropertyRequest;
use App\Property\Application\Property\Match\MatchPropertyService;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Infrastructure\ExternalSource\Property\Import\ImportConfiguration;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'property:import',
    description: 'Import a file with property listings',
)]
class ImportCommand extends Command
{
    public const BATCH_SIZE = 500;

    private ?FieldExtractor $fieldExtractor = null;

    private ?ExternalSource $source = null;

    /** @var CountryId[] */
    private array $countries = [];

    /** @param iterable<ImportConfiguration> $configurations */
    public function __construct(
        private readonly PropertyInfoExtractor $propertyInfoExtractor,
        private readonly ExternalIdExtractor $externalIdExtractor,
        private readonly MatchPropertyService $matchPropertyService,
        private readonly RegisterPropertyService $registerPropertyService,
        private readonly ImportCountriesService $importCountries,
        private readonly ImportDestinationsService $importDestinations,
        private readonly ImportBrandsService $importBrands,
        private readonly ImportCategoriesService $importCategories,
        private readonly EntityManagerInterface $manager,
        #[AutowireIterator('property.import.configuration')]
        private readonly iterable $configurations,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'The source of the properties (e.g., bioscore, restel)')
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the file')
            ->addOption('skip-countries', null, InputOption::VALUE_NONE, 'Skip countries import')
            ->addOption('skip-destinations', null, InputOption::VALUE_NONE, 'Skip destinations import')
            ->addOption('skip-brands', null, InputOption::VALUE_NONE, 'Skip brands import')
            ->addOption('skip-categories', null, InputOption::VALUE_NONE, 'Skip categories import')
            ->addOption('skip-properties', null, InputOption::VALUE_NONE, 'Skip properties import')
            ->addOption('create-countries', null, InputOption::VALUE_NONE)
            ->addOption('create-destinations', null, InputOption::VALUE_NONE)
            ->addOption('create-brands', null, InputOption::VALUE_NONE)
            ->addOption('create-categories', null, InputOption::VALUE_NONE)
            ->addOption('create-properties', null, InputOption::VALUE_NONE)
            ->addOption('offset-properties', null, InputOption::VALUE_REQUIRED, 'offset para propiedades', 0)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importando fichero de propiedades');

        $source = ExternalSource::tryFrom((string) $input->getArgument('source'));

        if (null === $source) {
            $io->error(sprintf('Fuente "%s" no reconocida.', (string) $input->getArgument('source')));

            return Command::FAILURE;
        }

        $this->source = $source;

        /** @var ImportConfiguration $configuration */
        foreach ($this->configurations as $configuration) {
            if ($configuration->getSource() === $this->source) {
                $this->fieldExtractor = $configuration->getFieldExtractor();
                break;
            }
        }

        if (null === $this->fieldExtractor) {
            $io->error(sprintf('No se ha encontrado configuración para la fuente "%s".', $this->source->value));

            return Command::FAILURE;
        }

        $filename = (string) $input->getArgument('file');

        if (!file_exists($filename)) {
            $io->error(sprintf('El fichero "%s" no existe.', $filename));

            return Command::FAILURE;
        }

        if (!is_readable($filename)) {
            $io->error(sprintf('El fichero "%s" no se puede leer.', $filename));

            return Command::FAILURE;
        }

        if (!(bool) $input->getOption('skip-countries')) {
            $this->countries($io, $filename, (bool) $input->getOption('create-countries'));
        }

        if (!(bool) $input->getOption('skip-destinations')) {
            $this->destinations($io, $filename, (bool) $input->getOption('create-destinations'));
        }

        if (!(bool) $input->getOption('skip-brands')) {
            $this->brands($io, $filename, (bool) $input->getOption('create-brands'));
        }

        if (!(bool) $input->getOption('skip-categories')) {
            $this->categories($io, $filename, (bool) $input->getOption('create-categories'));
        }

        if (!(bool) $input->getOption('skip-properties')) {
            $offset = (int) $input->getOption('offset-properties');

            $this->properties($io, $filename, (bool) $input->getOption('create-properties'), $offset);
        }

        return Command::SUCCESS;
    }

    protected function categories(SymfonyStyle $io, string $filename, bool $createIfNotExists): void
    {
        if (null === $this->fieldExtractor || !$this->fieldExtractor->hasField(Field::InfoCategoryName)) {
            $io->error('No se puede importar categorias para esta fuente.');

            return;
        }

        $file = fopen($filename, 'r');
        if (false === $file) {
            $io->error(sprintf('Could not open file "%s" for reading.', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        /** @var array<string, CategoryRequest> $categories */
        $categories = [];

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            /** @var array<int, string|null> $row */
            $categoryName = ucfirst(strtolower((string) $this->fieldExtractor->extract(Field::InfoCategoryName, $row)));
            $categoryId = $this->fieldExtractor->hasField(Field::InfoCategoryId) ? (string) $this->fieldExtractor->extract(Field::InfoCategoryId, $row) : md5($categoryName);

            if (!isset($categories[$categoryId])) {
                if (null === $this->source) {
                    return;
                }
                $categories[$categoryId] = new CategoryRequest(
                    name: $categoryName,
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $categoryId),
                    createIfNotExists: $createIfNotExists,
                );
            }
        }

        fclose($file);

        $result = $this->importCategories->__invoke(new ImportCategoriesRequest($categories), UserMother::systemUser());

        if (!$createIfNotExists && count($result->categories) > 0) {
            $outputFile = fopen('./var/categories-unmatched.csv', 'w');
            if (false === $outputFile) {
                $io->error('Could not open file "./var/categories-unmatched.csv" for writing.');

                return;
            }

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Source',
            ]);

            if (null === $this->source) {
                fclose($outputFile);

                return;
            }
            $attribute = $this->source->attribute();

            foreach ($result->categories as $categoryResult) {
                if (null !== $categoryResult->categoryId) {
                    continue;
                }

                $categoryId = null;
                if (null !== $categoryResult->externalId) {
                    $categoryId = $categoryResult->externalId->$attribute();
                }

                fputcsv($outputFile, [
                    $categoryId,
                    $categoryResult->name,
                    $attribute,
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf('Importadas %s marcas', $result->matched));
    }

    protected function brands(SymfonyStyle $io, string $filename, bool $createIfNotExists): void
    {
        if (null === $this->fieldExtractor || !$this->fieldExtractor->hasField(Field::InfoBrandName)) {
            $io->error('No se puede importar marcas para esta fuente.');

            return;
        }

        $file = fopen($filename, 'r');
        if (false === $file) {
            $io->error(sprintf('Could not open file "%s" for reading.', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        /** @var array<string, BrandRequest> $brands */
        $brands = [];

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            /** @var array<int, string|null> $row */
            $brandName = ucfirst(strtolower((string) $this->fieldExtractor->extract(Field::InfoBrandName, $row)));
            $brandId = $this->fieldExtractor->hasField(Field::InfoBrandId) ? (string) $this->fieldExtractor->extract(Field::InfoBrandId, $row) : md5($brandName);

            if (!isset($brands[$brandId])) {
                if (null === $this->source) {
                    return;
                }
                $brands[$brandId] = new BrandRequest(
                    name: $brandName,
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $brandId),
                    createIfNotExists: $createIfNotExists,
                );
            }
        }

        fclose($file);

        $result = $this->importBrands->__invoke(new ImportBrandsRequest($brands), UserMother::systemUser());

        if (!$createIfNotExists && count($result->brands) > 0) {
            $outputFile = fopen('./var/brands-unmatched.csv', 'w');
            if (false === $outputFile) {
                $io->error('Could not open file "./var/brands-unmatched.csv" for writing.');

                return;
            }

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Source',
            ]);

            if (null === $this->source) {
                fclose($outputFile);

                return;
            }
            $attribute = $this->source->attribute();

            foreach ($result->brands as $brandResult) {
                if (null !== $brandResult->brandId) {
                    continue;
                }

                $brandId = null;
                if (null !== $brandResult->externalId) {
                    $brandId = $brandResult->externalId->$attribute();
                }

                fputcsv($outputFile, [
                    $brandId,
                    $brandResult->name,
                    $attribute,
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf('Importadas %s marcas', $result->matched));
    }

    protected function countries(SymfonyStyle $io, string $filename, bool $createIfNotExists): void
    {
        if (null === $this->fieldExtractor || !$this->fieldExtractor->hasField(Field::InfoCountryName)) {
            $io->error('No se puede importar paises para esta fuente.');

            return;
        }

        $file = fopen($filename, 'r');
        if (false === $file) {
            $io->error(sprintf('Could not open file "%s" for reading.', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        /** @var array<string, CountryRequest> $countries */
        $countries = [];

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            /** @var array<int, string|null> $row */
            $countryName = ucfirst(strtolower((string) $this->fieldExtractor->extract(Field::InfoCountryName, $row)));
            $countryIso = (string) $this->fieldExtractor->extract(Field::InfoCountryIso, $row);
            $countryId = $this->fieldExtractor->hasField(Field::InfoCountryId) ? (string) $this->fieldExtractor->extract(Field::InfoCountryId, $row) : md5($countryName);

            if (!isset($countries[$countryId])) {
                if (null === $this->source) {
                    return;
                }
                $countries[$countryId] = new CountryRequest(
                    name: $countryName,
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $countryId),
                    iso: $countryIso,
                    createIfNotExists: $createIfNotExists,
                );
            }
        }

        fclose($file);

        $result = $this->importCountries->__invoke(new ImportCountriesRequest($countries), UserMother::systemUser());

        if (!$createIfNotExists && count($result->countries) > 0) {
            $outputFile = fopen('./var/countries-unmatched.csv', 'w');
            if (false === $outputFile) {
                $io->error('Could not open file "./var/countries-unmatched.csv" for writing.');

                return;
            }

            fputcsv($outputFile, [
                'Id', 'Nombre', 'ISO', 'Source',
            ]);

            if (null === $this->source) {
                fclose($outputFile);

                return;
            }
            $attribute = $this->source->attribute();

            foreach ($result->countries as $countryResult) {
                if (null !== $countryResult->countryId) {
                    continue;
                }

                $countryId = null;
                if (null !== $countryResult->externalId) {
                    $countryId = $countryResult->externalId->$attribute();
                }

                fputcsv($outputFile, [
                    $countryId,
                    $countryResult->name,
                    $countryResult->iso,
                    $attribute,
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf('Importados %s paises', $result->matched));
    }

    /**
     * @param array<int, string|null> $row
     */
    private function getCountryId(array $row): ?CountryId
    {
        if (null === $this->fieldExtractor || null === $this->source) {
            return null;
        }

        $countryName = ucfirst(strtolower((string) $this->fieldExtractor->extract(Field::InfoCountryName, $row)));
        $countryIso = (string) $this->fieldExtractor->extract(Field::InfoCountryIso, $row);
        $countryId = $this->fieldExtractor->hasField(Field::InfoCountryId) ? (string) $this->fieldExtractor->extract(Field::InfoCountryId, $row) : md5($countryName);

        if (isset($this->countries[$countryId])) {
            return $this->countries[$countryId];
        }

        $result = $this->importCountries->__invoke(new ImportCountriesRequest([
            new CountryRequest(
                name: $countryName,
                source: $this->source,
                externalId: ExternalId::fromSourceAndId($this->source, $countryId),
                iso: $countryIso,
                createIfNotExists: false,
            ),
        ]), UserMother::systemUser());

        if (!isset($result->countries[0]->countryId)) {
            return null;
        }

        $this->countries[$countryId] = $result->countries[0]->countryId;

        return $this->countries[$countryId];
    }

    protected function destinations(SymfonyStyle $io, string $filename, bool $createIfNotExists): void
    {
        if (null === $this->fieldExtractor || null === $this->source || !$this->fieldExtractor->hasField(Field::InfoDestinationName)) {
            $io->error('No se puede importar destinos para esta fuente.');

            return;
        }

        $file = fopen($filename, 'r');
        if (false === $file) {
            $io->error(sprintf('Could not open file "%s" for reading.', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        /** @var array<string, DestinationRequest> $destinations */
        $destinations = [];

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            /** @var array<int, string|null> $row */
            $destinationName = ucfirst(strtolower((string) $this->fieldExtractor->extract(Field::InfoDestinationName, $row)));
            $destinationId = $this->fieldExtractor->hasField(Field::InfoDestinationId) ? (string) $this->fieldExtractor->extract(Field::InfoDestinationId, $row) : md5($destinationName);

            $countryId = $this->getCountryId($row);

            if (!isset($destinations[$destinationId]) && $countryId instanceof CountryId) {
                $destinations[$destinationId] = new DestinationRequest(
                    name: $destinationName,
                    countryId: $countryId,
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $destinationId),
                    createIfNotExists: $createIfNotExists,
                );
            }
        }

        fclose($file);

        $result = $this->importDestinations->__invoke(new ImportDestinationsRequest($destinations), UserMother::systemUser());

        if (!$createIfNotExists && count($result->destinations) > 0) {
            $outputFile = fopen('./var/destinations-unmatched.csv', 'w');
            if (false === $outputFile) {
                $io->error('Could not open file "./var/destinations-unmatched.csv" for writing.');

                return;
            }

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Pais', 'Source',
            ]);

            $attribute = $this->source->attribute();

            foreach ($result->destinations as $destinationResult) {
                if (null !== $destinationResult->destinationId) {
                    continue;
                }

                $destinationId = null;
                if (null !== $destinationResult->externalId) {
                    $destinationId = $destinationResult->externalId->$attribute();
                }

                fputcsv($outputFile, [
                    $destinationId,
                    $destinationResult->name,
                    (string) $destinationResult->countryId,
                    $attribute,
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf('Importados %s destinos', $result->matched));
    }

    protected function properties(SymfonyStyle $io, string $filename, bool $createIfNotExists, int $offset = 0): void
    {
        if (null === $this->fieldExtractor || null === $this->source) {
            $io->error('No se puede importar propiedades para esta fuente.');

            return;
        }

        $file = fopen($filename, 'r');
        if (false === $file) {
            $io->error(sprintf('Could not open file "%s" for reading.', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        $progressBar = $io->createProgressBar();

        $imported = 0;

        if (!$createIfNotExists) {
            $outputFile = fopen('./var/properties-unmatched.csv', 'w');
            if (false === $outputFile) {
                $io->error('Could not open file "./var/properties-unmatched.csv" for writing.');

                return;
            }

            fputcsv($outputFile, [
                'Bioscore', 'Hotelbeds', 'Restel', 'Nombre', 'Dirección', 'CP', 'Latitud', 'Longitud', 'Tipo', 'Zona', 'DestinoID', 'Destino', 'PaisID', 'Pais', 'Marca', 'Categoria', 'Source',
            ]);
        }

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            $progressBar->advance();

            if ($offset > 0) {
                --$offset;

                continue;
            }

            /** @var array<int, string|null> $row */
            $propertyInfo = $this->propertyInfoExtractor->extract($this->fieldExtractor, $this->source, $row);
            $externalId = $this->externalIdExtractor->extract($this->fieldExtractor, $row);

            $propertyName = (string) $this->fieldExtractor->extract(Field::Name, $row);

            $request = new MatchPropertyRequest(
                name: $propertyName,
                info: $propertyInfo,
                externalId: $externalId,
                source: $this->source,
            );

            $response = $this->matchPropertyService->__invoke($request, UserMother::systemUser());

            $propertyId = $response->propertyId;

            if (null === $propertyId && $createIfNotExists) {
                $registerResponse = $this->registerPropertyService->__invoke(new RegisterPropertyRequest(
                    name: $propertyName,
                    info: $propertyInfo,
                    externalId: $externalId,
                ), UserMother::systemUser());

                $propertyId = $registerResponse->propertyId;
            } elseif (null === $propertyId) {
                fputcsv($outputFile, [
                    (string) ($externalId->bioscore() ?? ''),
                    (string) ($externalId->hotelbeds() ?? ''),
                    (string) ($externalId->restel() ?? ''),
                    $propertyName,
                    (string) $propertyInfo->address(),
                    (string) $propertyInfo->postalCode(),
                    (string) $propertyInfo->latitude(),
                    (string) $propertyInfo->longitude(),
                    $propertyInfo->propertyType()?->value ?: '',
                    (string) $propertyInfo->zone(),
                    (string) ($propertyInfo->destinationId() ?? ''),
                    (string) $this->fieldExtractor->extract(Field::InfoDestinationName, $row),
                    (string) ($propertyInfo->countryId() ?? ''),
                    (string) $this->fieldExtractor->extract(Field::InfoCountryName, $row),
                    (string) ($propertyInfo->brandId() ?? ''),
                    (string) ($propertyInfo->categoryId() ?? ''),
                    $this->source->value,
                ]);
            }

            ++$imported;

            if (0 === $imported % self::BATCH_SIZE) {
                $this->manager->clear();
                $this->manager->getUnitOfWork()->clear();
            }
        }

        if (!$createIfNotExists) {
            fclose($outputFile);
        }

        fclose($file);

        $io->success(sprintf('Importadas %s propiedades', $imported));
    }
}
