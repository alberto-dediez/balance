<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\ImportationProfile\Memorize;

use App\DataExchange\Application\ImportationProfile\Memorize\MemorizeImportationChoicesRequest;
use App\DataExchange\Application\ImportationProfile\Memorize\MemorizeImportationChoicesService;
use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationStatus;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileChoicesMemorized;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationProfileRepository;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile\ImportationProfileMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MemorizeImportationChoicesServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    /** @return ImportationColumnConfiguration[] */
    private function configurations(): array
    {
        return [
            new ImportationColumnConfiguration(
                column: 0,
                name: 'nombre',
                field: 'name',
                values: [],
                translations: [],
            ),
            new ImportationColumnConfiguration(
                column: 1,
                name: 'clase',
                field: 'class',
                values: [],
                translations: [
                    new ColumnValueTranslation(value: 'Clase Alpha', translation: 'alpha'),
                    new ColumnValueTranslation(value: 'Clase Beta', translation: null),
                ],
            ),
            new ImportationColumnConfiguration(
                column: 2,
                name: 'sin-emparejar',
                field: null,
                values: [],
                translations: [],
            ),
        ];
    }

    public function testCreatesNewProfileFromExecutedImportation(): void
    {
        $accountId = AccountId::generate();
        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/../../Importation/Register/superheroes.csv'),
            accountId: $accountId,
            status: ImportationStatus::Executed,
        );
        $importation->loadConfigurations($this->configurations());
        $importation->setDefaultValues(['unit' => 'kg']);

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $profileRepository = new InMemoryImportationProfileRepository();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($importationRepository, $profileRepository);

        $result = $sut(new MemorizeImportationChoicesRequest($importation->id()), $user);

        $profile = $profileRepository->find($result->importationProfileId);

        $this->assertNotNull($profile);
        $this->assertEquals(['nombre' => 'name', 'clase' => 'class'], $profile->fieldsByColumn());
        $this->assertEquals(['clase' => ['Clase Alpha' => 'alpha']], $profile->translationsByColumn());
        $this->assertEquals(['unit' => 'kg'], $profile->defaultValues());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertTrue($lastEvent instanceof ImportationProfileChoicesMemorized);
    }

    public function testMergesOntoExistingProfile(): void
    {
        $accountId = AccountId::generate();
        $existingProfile = ImportationProfileMother::from(
            importationProfileId: ImportationProfileId::generate(),
            type: 'measurements',
            accountId: $accountId,
            fieldsByColumn: ['edad' => 'age'],
        );

        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/../../Importation/Register/superheroes.csv'),
            accountId: $accountId,
            status: ImportationStatus::Executed,
        );
        $importation->loadConfigurations($this->configurations());

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $profileRepository = new InMemoryImportationProfileRepository();
        $profileRepository->add($existingProfile);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($importationRepository, $profileRepository);

        $result = $sut(new MemorizeImportationChoicesRequest($importation->id()), $user);

        $this->assertTrue($result->importationProfileId->equals($existingProfile->id()));

        $profile = $profileRepository->find($existingProfile->id());
        $this->assertNotNull($profile);
        $this->assertEquals(['edad' => 'age', 'nombre' => 'name', 'clase' => 'class'], $profile->fieldsByColumn());
    }

    public function testThrowsWhenImportationIsNotExecuted(): void
    {
        $this->expectException(\RuntimeException::class);

        $accountId = AccountId::generate();
        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile(__DIR__ . '/../../Importation/Register/superheroes.csv'),
            accountId: $accountId,
            status: ImportationStatus::Started,
        );

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($importationRepository, new InMemoryImportationProfileRepository());

        $sut(new MemorizeImportationChoicesRequest($importation->id()), $user);
    }

    public function testThrowsWhenImportationDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);

        $user = UserMother::ownerUser();

        $sut = $this->createSUT(new InMemoryImportationRepository(), new InMemoryImportationProfileRepository());

        $sut(new MemorizeImportationChoicesRequest(ImportationId::generate()), $user);
    }

    private function createSUT(InMemoryImportationRepository $importationRepository, InMemoryImportationProfileRepository $profileRepository): MemorizeImportationChoicesService
    {
        $authorizer = ImportationProfileAuthorizerMother::create();

        return new MemorizeImportationChoicesService($importationRepository, $profileRepository, $authorizer);
    }
}
