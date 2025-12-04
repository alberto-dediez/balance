<?php

namespace App\Tests\Unit\DataExchange\Application\Importation\Register;

use App\DataExchange\Application\Importation\Register\RegisterImportationRequest;
use App\DataExchange\Application\Importation\Register\RegisterImportationResponse;
use App\DataExchange\Application\Importation\Register\RegisterImportationService;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationRegistered;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterImportationServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = new InMemoryImportationRepository();

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate()
        );

        $sut = $this->createSUT($repository);

        $request = new RegisterImportationRequest(
            name: 'superheroes.csv',
            type: 'heroes',
            path: __DIR__ . '/superheroes.csv',
            offset: 0,
            extension: 'csv',
            accountId: $user->accountId()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterImportationResponse);
        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted instanceof Importation);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationRegistered);
        $this->assertEquals('superheroes.csv', $lastEvent->name());
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryImportationRepository();

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate()
        );

        $sut = $this->createSUT($repository);

        $request = new RegisterImportationRequest(
            name: 'superheroes.csv',
            type: 'heroes',
            path: __DIR__ . '/superheroes.csv',
            offset: 0,
            extension: 'csv',
            accountId: AccountId::generate()
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryImportationRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterImportationRequest(
            name: 'superheroes.csv',
            type: 'heroes',
            path: __DIR__ . '/superheroes.csv',
            offset: 0,
            extension: 'csv',
            accountId: AccountId::generate()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = ImportationAuthorizerMother::create();

        return new RegisterImportationService($repository, $authorizer);
    }
}
