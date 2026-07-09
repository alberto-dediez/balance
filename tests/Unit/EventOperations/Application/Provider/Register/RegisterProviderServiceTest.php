<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Provider\Register;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Application\Provider\Register\RegisterProviderRequest;
use App\EventOperations\Application\Provider\Register\RegisterProviderService;
use App\EventOperations\Domain\Model\Provider\ProviderRegistered;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterProviderServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ProviderRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-id-test');

        $request = new RegisterProviderRequest(
            name: 'Provider Test',
            vatNumber: 'B12345678',
            email: 'provider-test@example.com',
            accountId: $accountId,
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertNotEmpty((string) $result->providerId);
        $this->assertEquals(36, strlen((string) $result->providerId));
        $this->assertEquals('Provider Test', $result->name);

        $providerPersisted = $repository->find($result->providerId);

        $this->assertNotNull($providerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProviderRegistered);
        $this->assertEquals('Provider Test', $lastEvent->name());
        $this->assertTrue($providerPersisted->id()->equals($lastEvent->providerId()));
    }

    public function testIsHandle(): void
    {
        $repository = ProviderRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterProviderRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProviderRepository $repository): RegisterProviderService
    {
        return new RegisterProviderService(
            providerRepository: $repository,
            authorizer: new ProviderAuthorizer(),
        );
    }
}
