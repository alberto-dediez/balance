<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\DataExchange;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class DataExchangeContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getImportationRepository() : ImportationRepository
    {
        return $this->client->getContainer()->get(ImportationRepository::class);
    }

    #[Given('an importation with id :importationId from account :accountId exists')]
    public function anImportationWithIdFromAccountExists($importationId, $accountId): void
    {
        $filename = __DIR__ . '/file-to-import.csv';

        $importationFile = new ImportationFile(path: $filename);

        $importation = ImportationMother::fromIdAndAccountAndFile(id: $importationId, accountId: $accountId, file: $importationFile);

        $repository = $this->getImportationRepository();
        $repository->remove($importation);

        $repository->add($importation);
    }

    #[Given('importation :importationId has paired fields :fields')]
    public function importationHasPairedFields($importationId, $fields): void
    {
        $repository = $this->getImportationRepository();
        $importation = $repository->find(new ImportationId($importationId));

        foreach (explode('|', $fields) as $field) {
            list($name, $value) = explode(':', $field);

            $configuration = $importation->configurationFor($name);

            if (null === $configuration) {
                continue;
            }

            $configuration->field = $value;
        }

        $repository->add($importation);
    }

    #[Given('an executed importation with id :importationId from account :accountId exists')]
    public function anExecutedImportationWithIdFromAccountExists($importationId, $accountId): void
    {
        $filename = __DIR__ . '/file-to-import.csv';

        $importationFile = new ImportationFile(path: $filename);

        $importation = ImportationMother::fromIdAndAccountAndFile(id: $importationId, accountId: $accountId, file: $importationFile);

        $repository = $this->getImportationRepository();
        $repository->remove($importation);

        $importation->execute();

        $repository->add($importation);

        $repository->saveError($importation->id(), 3, 'Powerless');
    }
}
