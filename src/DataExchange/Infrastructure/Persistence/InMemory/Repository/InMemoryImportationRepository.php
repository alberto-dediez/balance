<?php

namespace App\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryImportationRepository implements ImportationRepository
{
    private ImportationDataRepository $dataRepository;

    public function __construct(private array $importations = [])
    {
        $this->dataRepository = new SqliteImportationDataRepository(
            __DIR__ . '/../../../../../../var/sqlite-test/'
        );
    }

    public function add(Importation $importation): void
    {
        if (isset($this->importations[(string) $importation->id()])) {
            $importation = $this->importations[(string) $importation->id()];
            if (count($importation->configurations()) === 0) {
                return ;
            }

            foreach ($importation->configurations() as $configuration) {
                $this->dataRepository->saveConfiguration($importation->id(), $configuration);
            }

            return ;
        }

        $this->dataRepository->create($importation);

        $this->importations[(string) $importation->id()] = $importation;
    }

    public function remove(Importation $importation): void
    {
        $this->dataRepository->remove($importation->id());

        if (!isset($this->importations[(string) $importation->id()])) {
            return ;
        }

        unset($this->importations[(string) $importation->id()]);
    }

    public function find(ImportationId $importationId): ?Importation
    {
        $importation = isset($this->importations[(string) $importationId])
            ? $this->importations[(string) $importationId]
            : null
        ;

        if ($importation === null) {
            return null;
        }

        if (count($importation->configurations()) === 0) {
            $importation->loadConfigurations(
                $this->dataRepository->getAllConfigurations($importation->id())
            );
        }

        return $importation;
    }

    public function getColumns(ImportationId $importationId): array
    {
        return $this->dataRepository->getColumns($importationId);
    }

    public function getData(ImportationId $importationId, int $rowNumber): array
    {
        return $this->dataRepository->getData($importationId, $rowNumber);
    }
}
