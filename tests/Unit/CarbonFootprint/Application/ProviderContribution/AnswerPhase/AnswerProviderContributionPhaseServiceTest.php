<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\AnswerPhase;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseRequest;
use App\CarbonFootprint\Application\ProviderContribution\AnswerPhase\AnswerProviderContributionPhaseService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStepStatus;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerProviderContributionPhaseServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private Combustible $combustible;
    private AnswerProviderContributionPhaseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();

        $this->combustible = CombustibleMother::registeredCombustible();
        $this->combustible->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
        $selector = new CombustibleEmissionFactorSelector($combustibleRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $selector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $selector, $calculator),
            ),
        ]);

        $this->sut = new AnswerProviderContributionPhaseService(
            new ProviderContributionAuthenticator($this->providerContributionRepository, $this->providerRepository, $this->userRepository),
            $this->providerContributionRepository,
            $this->eventCarbonFootprintRepository,
            new StageAnswersSynchronizer($handlers, $this->countryResolver()),
        );
    }

    public function testAnswersCombustiblesAndTagsTheActivityWithTheProviderId(): void
    {
        [$eventCarbonFootprint, $contribution] = $this->registeredContributionWithLinkedProviderUser();

        ($this->sut)(new AnswerProviderContributionPhaseRequest(
            $contribution->token(),
            Phase::Assembly,
            [$this->combustiblesSection()],
        ));

        $providerId = $contribution->providerId();
        $this->assertNotNull($providerId);

        $activityIds = $eventCarbonFootprint->activities()->filterByProviderId($providerId)->activityIds();
        $this->assertCount(1, $activityIds);

        $activity = $this->activityRepository->findById($activityIds[0]);
        $this->assertInstanceOf(CombustibleActivity::class, $activity);
    }

    public function testAnswerIsNotMarkedForOwnerActivities(): void
    {
        [$eventCarbonFootprint, $contribution] = $this->registeredContributionWithLinkedProviderUser();

        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, [$this->combustiblesSection()]));

        $ownerActivityIds = $eventCarbonFootprint->activities()->filterByProviderId(null)->activityIds();
        $this->assertCount(0, $ownerActivityIds);
    }

    public function testAnsweringAPhaseDoesNotMarkTheContributionAsAnswered(): void
    {
        [, $breakdownContribution] = $this->registeredContributionWithLinkedProviderUser();
        ($this->sut)(new AnswerProviderContributionPhaseRequest($breakdownContribution->token(), Phase::Breakdown, []));
        $persisted = $this->providerContributionRepository->find($breakdownContribution->id());
        $this->assertNotNull($persisted);
        $this->assertFalse($persisted->status()->isAnswered());
    }

    public function testAnsweringAPhaseAdvancesTheContributionStepStatus(): void
    {
        [, $contribution] = $this->registeredContributionWithLinkedProviderUser();

        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, []));

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertSame(ProviderContributionStepStatus::AssemblyAnswered, $persisted->stepStatus());
    }

    public function testAnsweringAnEarlierPhaseDoesNotRegressTheContributionStepStatus(): void
    {
        [, $contribution] = $this->registeredContributionWithLinkedProviderUser();

        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Celebration, []));
        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, []));

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertSame(ProviderContributionStepStatus::CelebrationAnswered, $persisted->stepStatus());
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        ($this->sut)(new AnswerProviderContributionPhaseRequest('invalid-token', Phase::Assembly, []));
    }

    public function testIgnoredContributionThrows(): void
    {
        [, $contribution] = $this->registeredContributionWithLinkedProviderUser();
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionIgnoredException::class);
        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, []));
    }

    public function testClosedEventCarbonFootprintThrows(): void
    {
        [$eventCarbonFootprint, $contribution] = $this->registeredContributionWithLinkedProviderUser();
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);
        $eventCarbonFootprint->complete(
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
        );

        $this->expectException(ProviderContributionClosedException::class);
        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, []));
    }

    public function testSectionThatDoesNotBelongToProviderSectionsThrows(): void
    {
        [, $contribution] = $this->registeredContributionWithLinkedProviderUser();

        $foreignSection = new SectionAnswer(Phase::Assembly->sections()[1], []);

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new AnswerProviderContributionPhaseRequest($contribution->token(), Phase::Assembly, [$foreignSection]));
    }

    private function combustiblesSection(): SectionAnswer
    {
        return new SectionAnswer(
            Phase::Assembly->providerSections()[0],
            [new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::Kilogram))],
        );
    }

    /** @return array{0: EventCarbonFootprint, 1: ProviderContribution} */
    private function registeredContributionWithLinkedProviderUser(): array
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id-' . uniqid(), 'account-id');
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

    private function countryResolver(): CarbonFootprintCountryResolver
    {
        return new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), PropertyRepositoryMother::inMemory());
    }
}
