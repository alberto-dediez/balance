<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\GetPhase;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MachineryFuelCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MobilityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\OvernightStayCatalogProvider;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\GetPhase\GetProviderContributionPhaseService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionPhaseSectionsBuilder;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserRequest;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetProviderContributionPhaseServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private GetProviderContributionPhaseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();

        $catalogProviders = new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new MachineryFuelCatalogProvider(MachineryFuelRepositoryMother::inMemory()),
            new OvernightStayCatalogProvider(OvernightStayRepositoryMother::inMemory()),
            new MobilityCatalogProvider(MobilityRepositoryMother::inMemory()),
        ]);

        $this->sut = new GetProviderContributionPhaseService(
            new ProviderContributionAuthenticator($this->providerContributionRepository, $this->providerRepository, $this->userRepository),
            $this->eventCarbonFootprintRepository,
            new ProviderContributionPhaseSectionsBuilder($catalogProviders, $this->activityRepository),
        );
    }

    public function testReturnsOnlyTheProviderSectionsForThatPhase(): void
    {
        [$eventCarbonFootprint, $contribution] = $this->registeredContribution();

        $response = ($this->sut)(new GetProviderContributionPhaseRequest($contribution->token(), Phase::Assembly));

        $this->assertCount(4, $response->sections);
        $this->assertSame(
            ['combustibles', 'machineryFuels', 'overnightStays', 'mobilities'],
            array_map(static fn($s) => $s->definition->key, $response->sections),
        );
    }

    public function testOnlySeesActivitiesBelongingToThatProvider(): void
    {
        [$eventCarbonFootprint, $contribution] = $this->registeredContribution();
        $providerId = $contribution->providerId();
        $this->assertNotNull($providerId);

        $ownActivity = CombustibleActivityMother::registered();
        $this->activityRepository->save($ownActivity);
        $eventCarbonFootprint->addActivity($ownActivity->id(), Phase::Assembly, Source::FixedFacilities, $providerId);

        $ownerActivity = CombustibleActivityMother::registered();
        $this->activityRepository->save($ownerActivity);
        $eventCarbonFootprint->addActivity($ownerActivity->id(), Phase::Assembly, Source::FixedFacilities);

        $otherProviderActivity = CombustibleActivityMother::registered();
        $this->activityRepository->save($otherProviderActivity);
        $eventCarbonFootprint->addActivity($otherProviderActivity->id(), Phase::Assembly, Source::FixedFacilities, ProviderId::generate());

        $response = ($this->sut)(new GetProviderContributionPhaseRequest($contribution->token(), Phase::Assembly));

        $combustibles = array_values(array_filter($response->sections, static fn($s) => 'combustibles' === $s->definition->key))[0];
        $this->assertCount(1, $combustibles->activities);
        $this->assertSame($ownActivity, $combustibles->activities[0]);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        ($this->sut)(new GetProviderContributionPhaseRequest('invalid-token', Phase::Assembly));
    }

    public function testIgnoredContributionThrows(): void
    {
        [, $contribution] = $this->registeredContribution();
        $contribution->ignore();

        $this->expectException(ProviderContributionIgnoredException::class);
        ($this->sut)(new GetProviderContributionPhaseRequest($contribution->token(), Phase::Assembly));
    }

    /** @return array{0: EventCarbonFootprint, 1: ProviderContribution} */
    private function registeredContribution(): array
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $registerUser = new RegisterUserService($this->userRepository, new Md5PasswordHasher(), new UserAuthorizer());
        $grantRole = new GrantRoleService($this->userRepository, new UserAuthorizer());
        $linkProviderUser = new LinkProviderUserService($this->providerRepository, new ProviderAuthorizer());

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $registeredUser = $registerUser(new RegisterUserRequest('provider-' . uniqid() . '@contribution.internal', 'secret', $owner->accountId()), $owner);
        $grantRole(new GrantRoleRequest($registeredUser->userId, Role::Provider), $owner);
        $linkProviderUser(new LinkProviderUserRequest($provider->id(), $registeredUser->userId), $owner);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId((string) $eventCarbonFootprint->id(), (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        return [$eventCarbonFootprint, $contribution];
    }
}
