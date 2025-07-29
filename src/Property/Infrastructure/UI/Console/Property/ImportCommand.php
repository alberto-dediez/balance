<?php

namespace App\Property\Infrastructure\UI\Console\Property;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Property\Domain\Model\Country\CountryId;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Property\Domain\Model\Property\ExternalId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use App\Property\Application\Property\Import\Field;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Application\Brand\Import\BrandRequest;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Application\Country\Import\CountryRequest;
use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Application\Category\Import\CategoryRequest;
use App\Property\Application\Brand\Import\ImportBrandsRequest;
use App\Property\Application\Brand\Import\ImportBrandsService;
use App\Property\Application\Property\Import\ExternalIdExtractor;
use App\Property\Application\Property\Match\MatchPropertyRequest;
use App\Property\Application\Property\Match\MatchPropertyService;
use App\Property\Application\Country\Import\ImportCountriesRequest;
use App\Property\Application\Country\Import\ImportCountriesService;
use App\Property\Application\Destination\Import\DestinationRequest;
use App\Property\Application\Property\Import\PropertyInfoExtractor;
use App\Property\Application\Category\Import\ImportCategoriesRequest;
use App\Property\Application\Category\Import\ImportCategoriesService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Application\Destination\Import\ImportDestinationsRequest;
use App\Property\Application\Destination\Import\ImportDestinationsService;

#[AsCommand(
    name: 'property:import',
    description: 'Import a file with property listings'
)]
class ImportCommand extends Command
{
    const BATCH_SIZE = 500;

    private FieldExtractor $fieldExtractor;

    private ExternalSource $source;

    private array $countries = [];

    public function __construct(
        private PropertyInfoExtractor $propertyInfoExtractor,
        private ExternalIdExtractor $externalIdExtractor,
        private MatchPropertyService $matchPropertyService,
        private RegisterPropertyService $registerPropertyService,
        private ImportCountriesService $importCountries,
        private ImportDestinationsService $importDestinations,
        private ImportBrandsService $importBrands,
        private ImportCategoriesService $importCategories,
        private EntityManagerInterface $manager,
        #[AutowireIterator('property.import.configuration')] 
        private iterable $configurations
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

        $source = ExternalSource::tryFrom($input->getArgument('source'));

        if ($source === null) {
            $io->error(sprintf('Fuente "%s" no reconocida.', $source));
            
            return Command::FAILURE;
        }

        $this->source = $source;

        foreach ($this->configurations as $configuration) {
            if ($configuration->getSource() === $source) {
                $this->fieldExtractor = $configuration->getFieldExtractor();
                break;
            }
        }

        if (null == $this->fieldExtractor) {
            $io->error(sprintf('No se ha encontrado configuración para la fuente "%s".', $source->value));
            
            return Command::FAILURE;
        }

        $filename = $input->getArgument('file');

        if (!file_exists($filename)) {
            $io->error(sprintf('El fichero "%s" no existe.', $filename));

            return Command::FAILURE;
        }

        if (!is_readable($filename)) {
            $io->error(sprintf('El fichero "%s" no se puede leer.', $filename));

            return Command::FAILURE;
        }

        if (!$input->getOption('skip-countries')) {
            $this->countries($io, $filename, $input->getOption('create-countries'));
        }

        if (!$input->getOption('skip-destinations')) {
            $this->destinations($io, $filename, $input->getOption('create-destinations'));
        }

        if (!$input->getOption('skip-brands')) {
            $this->brands($io, $filename, $input->getOption('create-brands'));
        }

        if (!$input->getOption('skip-categories')) {
            $this->categories($io, $filename, $input->getOption('create-categories'));
        }

        if (!$input->getOption('skip-properties')) {
            $offset = $input->getOption('offset-properties');

            $this->properties($io, $filename, $input->getOption('create-properties'), $offset);
        }

        return Command::SUCCESS;
    }

    protected function categories(SymfonyStyle $io, string $filename, bool $createIfNotExists)
    {
        if (!$this->fieldExtractor->hasField(Field::InfoCategoryName)) {
            $io->error('No se puede importar categorias para esta fuente.');

            return;
        }

        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $categories = [];

        while (false !== $row) {
            $row = fgetcsv($file, 2000, ';');

            if (!is_array($row)) {
                continue;
            }

            $categoryName = ucfirst(strtolower($this->fieldExtractor->extract(Field::InfoCategoryName, $row)));
            $categoryId = $this->fieldExtractor->hasField(Field::InfoCategoryId) ? $this->fieldExtractor->extract(Field::InfoCategoryId, $row) : md5($categoryName);

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = new CategoryRequest(
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $categoryId),
                    name: $categoryName,
                    createIfNotExists: $createIfNotExists
                );
            }
        }

        fclose($file);

        $result = $this->importCategories->__invoke(new ImportCategoriesRequest($categories), UserMother::systemUser());

        if (!$createIfNotExists && count($result->categories) > 0) {
            $outputFile = fopen('./var/categories-unmatched.csv', "w");

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Source'
            ]);

            $attribute = $this->source->attribute();

            foreach ($result->categories as $categoryResult) {
                if (null !== $categoryResult->categoryId) {
                    continue;
                }

                $categoryId = $categoryResult->externalId?->$attribute();

                fputcsv($outputFile, [
                    $categoryId,
                    $categoryResult->name,
                    $attribute
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf("Importadas %s marcas", $result->matched));
    }

    protected function brands(SymfonyStyle $io, string $filename, bool $createIfNotExists)
    {
        if (!$this->fieldExtractor->hasField(Field::InfoBrandName)) {
            $io->error('No se puede importar marcas para esta fuente.');

            return;
        }

        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $brands = [];

        while (false !== $row) {
            $row = fgetcsv($file, 2000, ';');

            if (!is_array($row)) {
                continue;
            }

            $brandName = ucfirst(strtolower($this->fieldExtractor->extract(Field::InfoBrandName, $row)));
            $brandId = $this->fieldExtractor->hasField(Field::InfoBrandId) ? $this->fieldExtractor->extract(Field::InfoBrandId, $row) : md5($brandName);

            if (!isset($brands[$brandId])) {
                $brands[$brandId] = new BrandRequest(
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $brandId),
                    name: $brandName,
                    createIfNotExists: $createIfNotExists
                );
            }
        }

        fclose($file);

        $result = $this->importBrands->__invoke(new ImportBrandsRequest($brands), UserMother::systemUser());

        if (!$createIfNotExists && count($result->brands) > 0) {
            $outputFile = fopen('./var/brands-unmatched.csv', "w");

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Source'
            ]);

            $attribute = $this->source->attribute();

            foreach ($result->brands as $brandResult) {
                if (null !== $brandResult->brandId) {
                    continue;
                }

                $brandId = $brandResult->externalId?->$attribute();

                fputcsv($outputFile, [
                    $brandId,
                    $brandResult->name,
                    $attribute
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf("Importadas %s marcas", $result->matched));
    }

    protected function countries(SymfonyStyle $io, string $filename, bool $createIfNotExists)
    {
        if (!$this->fieldExtractor->hasField(Field::InfoCountryName)) {
            $io->error('No se puede importar paises para esta fuente.');

            return;
        }

        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $countries = [];

        while (false !== $row) {
            $row = fgetcsv($file, 2000, ';');

            if (!is_array($row)) {
                continue;
            }

            $countryName = ucfirst(strtolower($this->fieldExtractor->extract(Field::InfoCountryName, $row)));
            $countryIso = $this->fieldExtractor->extract(Field::InfoCountryIso, $row);
            $countryId = $this->fieldExtractor->hasField(Field::InfoCountryId) ? $this->fieldExtractor->extract(Field::InfoCountryId, $row) : md5($countryName);

            if (!isset($countries[$countryId])) {
                $countries[$countryId] = new CountryRequest(
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $countryId),
                    name: $countryName,
                    iso: $countryIso,
                    createIfNotExists: $createIfNotExists
                );
            }
        }

        fclose($file);

        $result = $this->importCountries->__invoke(new ImportCountriesRequest($countries), UserMother::systemUser());

        if (!$createIfNotExists && count($result->countries) > 0) {
            $outputFile = fopen('./var/countries-unmatched.csv', "w");

            fputcsv($outputFile, [
                'Id', 'Nombre', 'ISO', 'Source'
            ]);

            $attribute = $this->source->attribute();

            foreach ($result->countries as $countryResult) {
                if (null !== $countryResult->countryId) {
                    continue;
                }

                $countryId = $countryResult->externalId?->$attribute();

                fputcsv($outputFile, [
                    $countryId,
                    $countryResult->name,
                    $countryResult->iso,
                    $attribute
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf("Importados %s paises", $result->matched));
    }

    private function getCountryId(array $row): ?CountryId
    {
        $countryName = ucfirst(strtolower($this->fieldExtractor->extract(Field::InfoCountryName, $row)));
        $countryIso = $this->fieldExtractor->extract(Field::InfoCountryIso, $row);
        $countryId = $this->fieldExtractor->hasField(Field::InfoCountryId) ? $this->fieldExtractor->extract(Field::InfoCountryId, $row) : md5($countryName);

        if (isset($this->countries[$countryId])) {
            return $this->countries[$countryId];
        }

        $result = $this->importCountries->__invoke(new ImportCountriesRequest([
            new CountryRequest(
                source: $this->source,
                externalId: ExternalId::fromSourceAndId($this->source, $countryId),
                name: $countryName,
                iso: $countryIso,
                createIfNotExists: false
            )
        ]), UserMother::systemUser());

        $this->countries[$countryId] = $result->countries[0]->countryId;

        return $this->countries[$countryId];
    }

    protected function destinations(SymfonyStyle $io, string $filename, bool $createIfNotExists)
    {
        if (!$this->fieldExtractor->hasField(Field::InfoDestinationName)) {
            $io->error('No se puede importar destinos para esta fuente.');

            return;
        }

        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $destinations = [];

        while (false !== $row) {
            $row = fgetcsv($file, 2000, ';');

            if (!is_array($row)) {
                continue;
            }

            $destinationName = ucfirst(strtolower($this->fieldExtractor->extract(Field::InfoDestinationName, $row)));
            $destinationId = $this->fieldExtractor->hasField(Field::InfoDestinationId) ? $this->fieldExtractor->extract(Field::InfoDestinationId, $row) : md5($destinationName);

            $countryId = $this->getCountryId($row);

            if (!isset($destinations[$destinationId]) && null !== $countryId) {
                $destinations[$destinationId] = new DestinationRequest(
                    name: $destinationName,
                    countryId: $countryId,
                    source: $this->source,
                    externalId: ExternalId::fromSourceAndId($this->source, $destinationId),
                    createIfNotExists: $createIfNotExists
                );
            }
        }

        fclose($file);

        $result = $this->importDestinations->__invoke(new ImportDestinationsRequest($destinations), UserMother::systemUser());

        if (!$createIfNotExists && count($result->destinations) > 0) {
            $outputFile = fopen('./var/destinations-unmatched.csv', "w");

            fputcsv($outputFile, [
                'Id', 'Nombre', 'Pais', 'Source'
            ]);

            $attribute = $this->source->attribute();

            foreach ($result->destinations as $destinationResult) {
                if (null !== $destinationResult->destinationId) {
                    continue;
                }

                $destinationId = $destinationResult->externalId?->$attribute();
                
                fputcsv($outputFile, [
                    $destinationId,
                    $destinationResult->name,
                    (string) $destinationResult->countryId,
                    $attribute
                ]);
            }

            fclose($outputFile);
        }

        $this->manager->clear();

        $io->success(sprintf("Importados %s destinos", $result->matched));
    }

    protected function properties(SymfonyStyle $io, string $filename, bool $createIfNotExists, $offset = 0)
    {
        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $progressBar = $io->createProgressBar();

        $imported = 0;

        if (!$createIfNotExists) {
            $outputFile = fopen('./var/properties-unmatched.csv', "w");

            fputcsv($outputFile, [
                'Bioscore', 'Hotelbeds', 'Restel', 'Nombre', 'Dirección', 'CP', 'Latitud', 'Longitud', 'Tipo', 'Zona', 'DestinoID', 'Destino', 'PaisID', 'Pais', 'Marca', 'Categoria', 'Source'
            ]);
        }
        
        while (false !== $row) {
            $row = fgetcsv($file, 2000, ';');

            if (!is_array($row)) {
                continue;
            }

            $progressBar->advance();

            if ($offset > 0) {
                $offset--;

                continue;
            }       

            $propertyInfo = $this->propertyInfoExtractor->extract($this->fieldExtractor, $this->source, $row);
            $externalId = $this->externalIdExtractor->extract($this->fieldExtractor, $row);

            $propertyName = $this->fieldExtractor->extract(Field::Name, $row);

            $request = new MatchPropertyRequest(
                name: $propertyName,
                info: $propertyInfo,
                externalId: $externalId,
                source: $this->source
            );  

            $response = $this->matchPropertyService->__invoke($request, UserMother::systemUser());

            $propertyId = $response->propertyId;

            if (null == $propertyId && $createIfNotExists) {
                $registerResponse = $this->registerPropertyService->__invoke(new RegisterPropertyRequest(
                    name: $propertyName,
                    info: $propertyInfo,
                    externalId: $externalId
                ), UserMother::systemUser());

                $propertyId = $registerResponse->propertyId;
            } elseif (null == $propertyId) {
                fputcsv($outputFile, [
                    $externalId->bioscore(), 
                    $externalId->hotelbeds(), 
                    $externalId->restel(), 
                    $propertyName, 
                    $propertyInfo->address(), 
                    $propertyInfo->postalCode(), 
                    $propertyInfo->latitude(), 
                    $propertyInfo->longitude(), 
                    $propertyInfo->propertyType()->value, 
                    $propertyInfo->zone(),
                    $propertyInfo->destinationId(), 
                    $this->fieldExtractor->extract(Field::InfoDestinationName, $row), 
                    $propertyInfo->countryId(),
                    $this->fieldExtractor->extract(Field::InfoCountryName, $row),
                    $propertyInfo->brandId(),
                    $propertyInfo->categoryId(),
                    $this->source->value
                ]);
            }

            $imported++;

            if (0 == $imported % self::BATCH_SIZE) {
                $this->manager->clear();
                $this->manager->getUnitOfWork()->clear();
            }
        }

        if (!$createIfNotExists) {
            fclose($outputFile);
        }

        fclose($file);

        $io->success(sprintf("Importadas %s propiedades", $imported));
    }
}