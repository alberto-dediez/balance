<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Provider\Remove;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Application\Provider\Remove\RemoveProviderRequest;
use App\EventOperations\Application\Provider\Remove\RemoveProviderService;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRemoved;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveProviderServiceTest extends TestCase
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

        $accountId = new AccountId('account-id-test');

        $provider = ProviderMother::fromNameAndAccountAndId('Provider', 'account-id-test', (string) ProviderId::generate());

        $repository->add($provider);

        $sut = $this->createSUT($repository);

        $sut(new RemoveProviderRequest($provider->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProviderRemoved);
        $this->assertTrue($provider->id()->equals($lastEvent->providerId()));
    }

    private function createSUT(ProviderRepository $repository): RemoveProviderService
    {
        return new RemoveProviderService(
            repository: $repository,
            authorizer: new ProviderAuthorizer(),
        );
    }
}
