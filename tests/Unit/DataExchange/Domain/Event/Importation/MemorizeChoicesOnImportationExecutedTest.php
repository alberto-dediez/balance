<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Event\Importation;

use App\DataExchange\Application\ImportationProfile\Memorize\MemorizeImportationChoicesService;
use App\DataExchange\Domain\Event\Importation\MemorizeChoicesOnImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileChoicesMemorized;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationProfileRepository;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MemorizeChoicesOnImportationExecutedTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    private function csvPath(): string
    {
        return __DIR__ . '/../../../Application/Importation/Register/superheroes.csv';
    }

    public function testMemorizesChoicesWhenImportationIsExecuted(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($user);

        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile($this->csvPath()),
            accountId: $accountId,
        );
        $importation->register('file.csv', 'measurements', $accountId, $user->id());
        $importation->loadConfigurations([
            new ImportationColumnConfiguration(
                column: 0,
                name: 'nombre',
                field: 'name',
                values: [],
                translations: [
                    new ColumnValueTranslation(value: 'Clase Alpha', translation: 'alpha'),
                ],
            ),
        ]);

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $profileRepository = new InMemoryImportationProfileRepository();

        $memorizeImportationChoices = new MemorizeImportationChoicesService(
            $importationRepository,
            $profileRepository,
            ImportationProfileAuthorizerMother::create(),
        );

        new MemorizeChoicesOnImportationExecuted(
            $importationRepository,
            $userRepository,
            $memorizeImportationChoices,
        );

        $importation->startExecution();
        $importation->finishExecution();

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof ImportationProfileChoicesMemorized);

        $profile = $profileRepository->findByTypeAndAccount('measurements', $accountId);
        $this->assertNotNull($profile);
        $this->assertEquals(['nombre' => 'name'], $profile->fieldsByColumn());
    }

    public function testDoesNothingWhenImportationHasNoRegisteredByUser(): void
    {
        $accountId = AccountId::generate();

        $importation = ImportationMother::from(
            importationId: ImportationId::generate(),
            name: 'file.csv',
            type: 'measurements',
            importationFile: new ImportationFile($this->csvPath()),
            accountId: $accountId,
        );

        $importationRepository = new InMemoryImportationRepository();
        $importationRepository->add($importation);

        $profileRepository = new InMemoryImportationProfileRepository();

        $memorizeImportationChoices = new MemorizeImportationChoicesService(
            $importationRepository,
            $profileRepository,
            ImportationProfileAuthorizerMother::create(),
        );

        new MemorizeChoicesOnImportationExecuted(
            $importationRepository,
            new InMemoryUserRepository(),
            $memorizeImportationChoices,
        );

        $importation->startExecution();
        $importation->finishExecution();

        $this->assertNull($profileRepository->findByTypeAndAccount('measurements', $accountId));
    }
}
