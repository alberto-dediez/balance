<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\ImportationProfile\Recall;

use App\DataExchange\Application\ImportationProfile\Recall\RecallImportationChoicesRequest;
use App\DataExchange\Application\ImportationProfile\Recall\RecallImportationChoicesService;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationProfileRepository;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile\ImportationProfileMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RecallImportationChoicesServiceTest extends TestCase
{
    /** @return ImportationColumnConfiguration[] */
    private function configurations(): array
    {
        return [
            new ImportationColumnConfiguration(
                column: 0,
                name: 'nombre',
                field: null,
                values: [],
                translations: [],
            ),
        ];
    }

    public function testReturnsImportationChoicesWhenNoProfileExists(): void
    {
        $accountId = AccountId::generate();
        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/../../Importation/Register/superheroes.csv'),
            accountId: $accountId,
        );
        $importation->loadConfigurations($this->configurations());

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($importationRepository, new InMemoryImportationProfileRepository());

        $response = $sut(new RecallImportationChoicesRequest($importation->id()), $user);

        $this->assertNull($response->configurations[0]->field);
        $this->assertEquals($importation->defaultValues(), $response->defaultValues);
    }

    public function testPrefillsChoicesFromExistingProfile(): void
    {
        $accountId = AccountId::generate();
        $profile = ImportationProfileMother::from(
            importationProfileId: ImportationProfileId::generate(),
            type: 'measurements',
            accountId: $accountId,
            fieldsByColumn: ['nombre' => 'name'],
            defaultValues: ['unit' => 'kg'],
        );

        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/../../Importation/Register/superheroes.csv'),
            accountId: $accountId,
        );
        $importation->loadConfigurations($this->configurations());

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $profileRepository = new InMemoryImportationProfileRepository();
        $profileRepository->add($profile);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($importationRepository, $profileRepository);

        $response = $sut(new RecallImportationChoicesRequest($importation->id()), $user);

        $this->assertSame('name', $response->configurations[0]->field);
        $this->assertEquals(['unit' => 'kg'], $response->defaultValues);
    }

    private function createSUT(InMemoryImportationRepository $importationRepository, InMemoryImportationProfileRepository $profileRepository): RecallImportationChoicesService
    {
        $authorizer = ImportationProfileAuthorizerMother::create();

        return new RecallImportationChoicesService($importationRepository, $profileRepository, $authorizer);
    }
}
