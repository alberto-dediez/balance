<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Console\Property;

use App\Property\Application\Property\Import\ExternalIdExtractor;
use App\Property\Application\Property\Import\Field;
use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Application\Property\Import\PropertyInfoExtractor;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
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
    name: 'property:property:load',
    description: 'Load a file with property listings',
)]
class PropertyLoadCommand extends Command
{
    public const BATCH_SIZE = 500;

    private ?FieldExtractor $fieldExtractor = null;

    private ?ExternalSource $source = null;

    /** @param iterable<ImportConfiguration> $configurations */
    public function __construct(
        private readonly PropertyInfoExtractor $propertyInfoExtractor,
        private readonly ExternalIdExtractor $externalIdExtractor,
        private readonly RegisterPropertyService $registerPropertyService,
        private readonly PropertyRepository $propertyRepository,
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

        $offset = (int) $input->getOption('offset-properties');

        $this->properties($io, $filename, $offset);

        return Command::SUCCESS;
    }

    protected function properties(SymfonyStyle $io, string $filename, int $offset = 0): void
    {
        $file = fopen($filename, 'r');

        if (false === $file) {
            $io->error(sprintf('No se pudo abrir el fichero "%s".', $filename));

            return;
        }

        // La primera fila es la cabecera con los nombres de los campos
        fgetcsv($file, 2000, ';');

        $progressBar = $io->createProgressBar();

        $imported = 0;

        while (false !== ($row = fgetcsv($file, 2000, ';'))) {
            $progressBar->advance();

            if ($offset > 0) {
                --$offset;

                continue;
            }

            /* @var array<string, string|null> $row */
            if (null === $this->fieldExtractor || null === $this->source) {
                $io->error('Error en la configuración del extractor o la fuente.');

                return;
            }

            $externalId = $this->externalIdExtractor->extract($this->fieldExtractor, $row);

            $propertyId = $this->findByMainExternalId($externalId, $this->source);

            if ($propertyId instanceof PropertyId) {
                continue;
            }

            $propertyInfo = $this->propertyInfoExtractor->extract($this->fieldExtractor, $this->source, $row);
            $propertyName = (string) $this->fieldExtractor->extract(Field::Name, $row);

            $registerResponse = $this->registerPropertyService->__invoke(new RegisterPropertyRequest(
                name: $propertyName,
                info: $propertyInfo,
                externalId: $externalId,
            ), UserMother::systemUser());

            ++$imported;

            if (0 === $imported % self::BATCH_SIZE) {
                $this->manager->clear();
                $this->manager->getUnitOfWork()->clear();
            }
        }

        fclose($file);

        $io->success(sprintf('Importadas %s propiedades', $imported));
    }

    private function findByMainExternalId(ExternalId $externalId, ExternalSource $source): ?PropertyId
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $property = $this->propertyRepository->findBySourceAndExternalId($source, $externalId);

        return $property instanceof Property ? $property->id() : null;
    }
}
