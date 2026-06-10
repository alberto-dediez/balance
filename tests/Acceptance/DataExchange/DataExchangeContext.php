<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\DataExchange;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportationStatus;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class DataExchangeContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getImportationRepository(): ImportationRepository
    {
        return $this->client->getContainer()->get(ImportationRepository::class);
    }

    public function getExportationRepository(): ExportationRepository
    {
        return $this->client->getContainer()->get(ExportationRepository::class);
    }

    private function getExportStorage(): ExportStorage
    {
        return $this->client->getContainer()->get(ExportStorage::class);
    }

    #[Given('an importation with id :importationId from account :accountId exists')]
    public function anImportationWithIdFromAccountExists(string $importationId, string $accountId): void
    {
        $filename = __DIR__ . '/file-to-import.csv';

        $importationFile = new ImportationFile(path: $filename);

        $importation = ImportationMother::fromIdAndAccountAndFile(id: $importationId, accountId: $accountId, file: $importationFile);

        $repository = $this->getImportationRepository();
        $repository->remove($importation);

        $repository->add($importation);
    }

    #[Given('importation :importationId has paired fields :fields')]
    public function importationHasPairedFields(string $importationId, string $fields): void
    {
        $repository = $this->getImportationRepository();
        $importation = $repository->find(new ImportationId($importationId));
        assert(null !== $importation);

        foreach (explode('|', $fields) as $field) {
            [$name, $value] = explode(':', $field);

            $configuration = $importation->configurationFor($name);

            if (!$configuration instanceof ImportationColumnConfiguration) {
                continue;
            }

            $configuration->field = $value;
        }

        $repository->add($importation);
    }

    #[Given('an executed importation with id :importationId from account :accountId exists')]
    public function anExecutedImportationWithIdFromAccountExists(string $importationId, string $accountId): void
    {
        $filename = __DIR__ . '/file-to-import.csv';

        $importationFile = new ImportationFile(path: $filename);

        $importation = ImportationMother::fromIdAndAccountAndFile(id: $importationId, accountId: $accountId, file: $importationFile);

        $repository = $this->getImportationRepository();
        $repository->remove($importation);

        $importation->finishExecution();

        $repository->add($importation);

        $repository->saveResult($importation->id(), 3, 'Powerless');
    }

    #[Given('a completed exportation with id :id of type :type from account :accountId exists')]
    public function aCompletedExportationExists(string $id, string $type, string $accountId): void
    {
        $this->persistCompletedExportation($id, $type, $accountId, new \DateTimeImmutable('+7 days'));
    }

    #[Given('an expired exportation with id :id of type :type from account :accountId exists')]
    public function anExpiredExportationExists(string $id, string $type, string $accountId): void
    {
        $this->persistCompletedExportation($id, $type, $accountId, new \DateTimeImmutable('-1 day'));
    }

    private function persistCompletedExportation(string $id, string $type, string $accountId, \DateTimeImmutable $validUntil): void
    {
        $exportationId = new ExportationId($id);
        $account = new AccountId($accountId);

        $temporaryPath = (string) tempnam(sys_get_temp_dir(), 'export_acceptance_');
        file_put_contents($temporaryPath, 'content');

        $storedFile = $this->getExportStorage()->store($exportationId, $account, 'xlsx', $temporaryPath);

        @unlink($temporaryPath);

        $exportation = new Exportation(
            id: $exportationId,
            type: $type,
            status: ExportationStatus::Completed,
            format: ExportationFormat::Xlsx,
            columns: ['username'],
            accountId: $account,
            file: $storedFile,
            registeredAt: new \DateTimeImmutable('-1 hour'),
            validUntil: $validUntil,
        );

        $this->getExportationRepository()->add($exportation);
    }
}
