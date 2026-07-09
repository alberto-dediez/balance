<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Provider\Update;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Application\Provider\Update\UpdateProviderRequest;
use App\EventOperations\Application\Provider\Update\UpdateProviderService;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\EventOperations\Domain\Model\Provider\ProviderUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateProviderServiceTest extends TestCase
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

        $request = new UpdateProviderRequest(
            providerId: $provider->id(),
            name: 'Updated Name',
            vatNumber: 'B87654321',
            street: 'Calle Mayor 1',
            postalCode: '28001',
            city: 'Madrid',
            country: 'España',
            email: 'updated-provider@example.com',
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertEquals('Updated Name', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProviderUpdated);
        $this->assertEquals('Updated Name', $lastEvent->name());
    }

    private function createSUT(ProviderRepository $repository): UpdateProviderService
    {
        return new UpdateProviderService(
            providerRepository: $repository,
            authorizer: new ProviderAuthorizer(),
        );
    }
}
