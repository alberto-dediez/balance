<?php

namespace App\Tests\Unit\DataExchange\Application\Importation\PairFields;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationResponse;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationService;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationConfigurationUpdated;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PairFieldsImportationServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/../Register/superheroes.csv'
        );

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', $importationFile);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $importation->accountId()
        );

        $sut = $this->createSUT($repository);

        $request = new PairFieldsImportationRequest(
            importationId: $importation->id(),
            configurations: $importation->configurations()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof PairFieldsImportationResponse);
        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted instanceof Importation);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationConfigurationUpdated);
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $importation = ImportationMother::random();

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate()
        );

        $sut = $this->createSUT($repository);

        $request = new PairFieldsImportationRequest(
            importationId: $importation->id(),
            configurations: []
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryImportationRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new PairFieldsImportationRequest(
            importationId: ImportationId::generate(),
            configurations: []
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = ImportationAuthorizerMother::create();

        return new PairFieldsImportationService($repository, $authorizer);
    }
}
