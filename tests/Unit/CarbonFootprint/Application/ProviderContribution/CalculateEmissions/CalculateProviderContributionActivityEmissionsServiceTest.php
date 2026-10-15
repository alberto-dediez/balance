<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\ProviderContribution\CalculateEmissions\CalculateProviderContributionActivityEmissionsRequest;
use App\CarbonFootprint\Application\ProviderContribution\CalculateEmissions\CalculateProviderContributionActivityEmissionsService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
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
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CalculateProviderContributionActivityEmissionsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private Combustible $combustible;
    private CalculateProviderContributionActivityEmissionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();

        $this->combustible = CombustibleMother::registeredCombustible();
        $this->combustible->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->sut = new CalculateProviderContributionActivityEmissionsService(
            new ProviderContributionAuthenticator($this->providerContributionRepository, $this->providerRepository, $this->userRepository),
            $this->eventCarbonFootprintRepository,
            new ActivityEmissionsResolver(
                new EmissionFactorSelectorRegistry([new CombustibleEmissionFactorSelector($combustibleRepository)]),
                EmissionsCalculatorMother::create(),
            ),
        );
    }

    public function testCalculatesEmissions(): void
    {
        $contribution = $this->registeredContribution();

        $response = ($this->sut)(new CalculateProviderContributionActivityEmissionsRequest(
            token: $contribution->token(),
            activityType: ActivityType::Combustible,
            itemId: (string) $this->combustible->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ));

        $this->assertSame('15', $response->emissions->co2eq());
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        ($this->sut)(new CalculateProviderContributionActivityEmissionsRequest(
            token: 'invalid-token',
            activityType: ActivityType::Combustible,
            itemId: (string) $this->combustible->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ));
    }

    public function testIgnoredContributionThrows(): void
    {
        $contribution = $this->registeredContribution();
        $contribution->ignore();

        $this->expectException(ProviderContributionIgnoredException::class);
        ($this->sut)(new CalculateProviderContributionActivityEmissionsRequest(
            token: $contribution->token(),
            activityType: ActivityType::Combustible,
            itemId: (string) $this->combustible->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ));
    }

    private function registeredContribution(): ProviderContribution
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

        return $contribution;
    }
}
