<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Importation\PairFields;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationService;
use App\DataExchange\Domain\Model\Importation\ImportationConfigurationUpdated;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\DataExchange\Infrastructure\Persistence\PDO\Repository\SqliteImportationDataRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\TemporaryDirectory;
use PHPUnit\Framework\TestCase;

class PairFieldsImportationServiceTest extends TestCase
{
    use TemporaryDirectory;

    private string $storagePath;

    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        $this->storagePath = $this->temporaryDirectoryPath('importation-data-test');

        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        $this->removeTemporaryDirectory($this->storagePath);

        parent::tearDown();
    }

    public function testUseCase(): void
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/../Register/superheroes.csv',
        );

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', $importationFile);

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository($this->storagePath));
        $repository->add($importation);

        $accountId = $importation->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );

        $sut = $this->createSUT($repository);

        $request = new PairFieldsImportationRequest(
            importationId: $importation->id(),
            configurations: $importation->configurations(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationConfigurationUpdated);
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $importation = ImportationMother::random();

        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository($this->storagePath));
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate(),
        );

        $sut = $this->createSUT($repository);

        $request = new PairFieldsImportationRequest(
            importationId: $importation->id(),
            configurations: [],
        );

        $sut($request, $user);
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryImportationRepository(dataRepository: new SqliteImportationDataRepository($this->storagePath));

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new PairFieldsImportationRequest(
            importationId: ImportationId::generate(),
            configurations: [],
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryImportationRepository $repository): PairFieldsImportationService
    {
        $authorizer = ImportationAuthorizerMother::create();

        return new PairFieldsImportationService($repository, $authorizer);
    }
}
