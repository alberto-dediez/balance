<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo\AnswerProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo\AnswerProviderContributionGeneralInfoService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionAnswered;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AnswerProviderContributionGeneralInfoServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private SpyDomainEventSubscriber $spySubscriber;
    private AnswerProviderContributionGeneralInfoService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut = new AnswerProviderContributionGeneralInfoService(
            new ProviderContributionAuthenticator($this->providerContributionRepository, $this->providerRepository, $this->userRepository),
            $this->providerContributionRepository,
            $this->eventCarbonFootprintRepository,
        );
    }

    public function testUpdatesTheGeneralInfo(): void
    {
        $contribution = $this->registeredContribution();

        ($this->sut)(new AnswerProviderContributionGeneralInfoRequest(
            $contribution->token(),
            companyName: 'New name',
            companyType: 'Catering',
            service: 'Comida',
            workersAssembly: 2,
            workersCelebration: 3,
            workersBreakdown: 1,
        ));

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertSame('New name', $persisted->companyName());
        $this->assertSame(2, $persisted->workersAssembly());
    }

    public function testDoesNotMarkTheContributionAsAnswered(): void
    {
        $contribution = $this->registeredContribution();

        ($this->sut)(new AnswerProviderContributionGeneralInfoRequest($contribution->token(), null, null, null, null, null, null));

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertFalse($persisted->status()->isAnswered());

        $events = array_filter($this->spySubscriber->events(), static fn($e) => $e instanceof ProviderContributionAnswered);
        $this->assertEmpty($events);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        ($this->sut)(new AnswerProviderContributionGeneralInfoRequest('invalid-token', null, null, null, null, null, null));
    }

    public function testIgnoredContributionThrows(): void
    {
        $contribution = $this->registeredContribution();
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionIgnoredException::class);
        ($this->sut)(new AnswerProviderContributionGeneralInfoRequest($contribution->token(), null, null, null, null, null, null));
    }

    public function testClosedEventCarbonFootprintThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);
        $eventCarbonFootprint->complete(totalEmissions: new Emissions(), emissionsPerAttendee: new Emissions());
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = $this->registeredContributionWithLinkedProviderUser((string) $eventCarbonFootprint->id());

        $this->expectException(ProviderContributionClosedException::class);
        ($this->sut)(new AnswerProviderContributionGeneralInfoRequest($contribution->token(), null, null, null, null, null, null));
    }

    private function registeredContribution(): ProviderContribution
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $this->registeredContributionWithLinkedProviderUser((string) $eventCarbonFootprint->id());
    }

    private function registeredContributionWithLinkedProviderUser(string $eventCarbonFootprintId): ProviderContribution
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $registerUser = new RegisterUserService($this->userRepository, new Md5PasswordHasher(), new UserAuthorizer());
        $grantRole = new GrantRoleService($this->userRepository, new UserAuthorizer());
        $linkProviderUser = new LinkProviderUserService($this->providerRepository, new ProviderAuthorizer());

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $registeredUser = $registerUser(new RegisterUserRequest('provider-' . uniqid() . '@contribution.internal', 'secret', $owner->accountId()), $owner);
        $grantRole(new GrantRoleRequest($registeredUser->userId, Role::Provider), $owner);
        $linkProviderUser(new LinkProviderUserRequest($provider->id(), $registeredUser->userId), $owner);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId($eventCarbonFootprintId, (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        return $contribution;
    }
}
