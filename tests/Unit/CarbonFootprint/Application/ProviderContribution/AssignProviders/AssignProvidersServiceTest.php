<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\AssignProviders;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignedProvider;
use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignProvidersRequest;
use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignProvidersService;
use App\CarbonFootprint\Application\ProviderContribution\Register\RegisterProviderContributionService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AssignProvidersServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private AssignProvidersService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();

        $registerProviderContribution = new RegisterProviderContributionService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->providerRepository,
            $this->providerContributionRepository,
        );

        $this->sut = new AssignProvidersService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->providerContributionRepository,
            $registerProviderContribution,
        );
    }

    public function testAssigningANewProviderRegistersAPendingContribution(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        ($this->sut)(
            new AssignProvidersRequest($eventCarbonFootprint->id(), [new AssignedProvider($provider->id())]),
            $this->ownerUser(),
        );

        $contributions = $this->providerContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertTrue($contributions[0]->status()->isPending());
    }

    public function testUnassigningAProviderIgnoresItsContribution(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        ($this->sut)(
            new AssignProvidersRequest($eventCarbonFootprint->id(), [new AssignedProvider($provider->id())]),
            $this->ownerUser(),
        );

        ($this->sut)(
            new AssignProvidersRequest($eventCarbonFootprint->id(), []),
            $this->ownerUser(),
        );

        $contributions = $this->providerContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertTrue($contributions[0]->status()->isIgnored());
    }

    public function testReassigningAPreviouslyUnassignedProviderUnignoresIt(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        ($this->sut)(new AssignProvidersRequest($eventCarbonFootprint->id(), [new AssignedProvider($provider->id())]), $this->ownerUser());
        ($this->sut)(new AssignProvidersRequest($eventCarbonFootprint->id(), []), $this->ownerUser());
        ($this->sut)(new AssignProvidersRequest($eventCarbonFootprint->id(), [new AssignedProvider($provider->id())]), $this->ownerUser());

        $contributions = $this->providerContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertFalse($contributions[0]->status()->isIgnored());
    }

    public function testAssigningProvidersMarksTheEventCarbonFootprintAsProvidersAnswered(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        ($this->sut)(
            new AssignProvidersRequest($eventCarbonFootprint->id(), [new AssignedProvider($provider->id())]),
            $this->ownerUser(),
        );

        $this->assertTrue($eventCarbonFootprint->status()->isProvidersAnswered());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new AssignProvidersRequest($eventCarbonFootprint->id(), []),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
