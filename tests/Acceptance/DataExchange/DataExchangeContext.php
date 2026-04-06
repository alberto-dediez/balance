<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\DataExchange;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
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
        assert($importation instanceof Importation);

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
}
