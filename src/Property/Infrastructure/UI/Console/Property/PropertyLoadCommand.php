<?php

namespace App\Property\Infrastructure\UI\Console\Property;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use App\Property\Application\Property\Import\Field;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Application\Property\Import\ExternalIdExtractor;
use App\Property\Application\Property\Import\PropertyInfoExtractor;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;

#[AsCommand(
    name: 'property:property:load',
    description: 'Load a file with property listings'
)]
class PropertyLoadCommand extends Command
{
    const BATCH_SIZE = 500;

    private FieldExtractor $fieldExtractor;

    private ExternalSource $source;

    public function __construct(
        private PropertyInfoExtractor $propertyInfoExtractor,
        private ExternalIdExtractor $externalIdExtractor,
        private RegisterPropertyService $registerPropertyService,
        private PropertyRepository $propertyRepository,
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

        $offset = $input->getOption('offset-properties');

        $this->properties($io, $filename, $offset);

        return Command::SUCCESS;
    }

    protected function properties(SymfonyStyle $io, string $filename, $offset = 0)
    {
        $file = fopen($filename, "r");

        //La primera fila es la cabecera con los nombres de los campos
        $row = fgetcsv($file, 2000, ';');

        $progressBar = $io->createProgressBar();

        $imported = 0;

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

            $externalId = $this->externalIdExtractor->extract($this->fieldExtractor, $row);

            $propertyId = $this->findByMainExternalId($externalId, $this->source);

            if (null !== $propertyId) {
                continue;
            }

            $propertyInfo = $this->propertyInfoExtractor->extract($this->fieldExtractor, $this->source, $row);
            $propertyName = $this->fieldExtractor->extract(Field::Name, $row);
            
            $registerResponse = $this->registerPropertyService->__invoke(new RegisterPropertyRequest(
                name: $propertyName,
                info: $propertyInfo,
                externalId: $externalId
            ), UserMother::systemUser());

            $imported++;

            if (0 == $imported % self::BATCH_SIZE) {
                $this->manager->clear();
                $this->manager->getUnitOfWork()->clear();
            }
        }

        fclose($file);

        $io->success(sprintf("Importadas %s propiedades", $imported));
    }

    private function findByMainExternalId(ExternalId $externalId, ExternalSource $source): ?PropertyId
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $property = $this->propertyRepository->findBySourceAndExternalId($source, $externalId);

        return null !== $property ? $property->id() : null;
    }
}