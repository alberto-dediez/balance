<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\Register\RegisterOperatorContributionRequest;
use App\CarbonFootprint\Application\OperatorContribution\Register\RegisterOperatorContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRegistered;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\UpdateOperatorContact\UpdatePropertyOperatorContactService;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyOperatorContactUpdated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;
use App\Tests\Unit\Security\Application\User\SystemActor\SystemActorResolverMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentImpersonation\StubCurrentImpersonationProvider;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

class RegisterOperatorContributionServiceTest extends OperatorContributionServiceTestCase
{
    private InMemoryUserRepository $userRepository;
    private SpyDomainEventSubscriber $spySubscriber;
    private SystemActorResolver $systemActor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->systemActor = SystemActorResolverMother::withPaymentUser();
    }

    public function testRegistersTheContributionWithATechnicalUser(): void
    {
        $footprint = $this->companyFootprint();
        $this->property();

        $response = $this->createSUT()($this->request($footprint), $this->owner());

        $contribution = $this->contributionRepository->find($response->contributionId);
        $this->assertNotNull($contribution);
        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertSame(Source::Franchises, $contribution->source());
        $this->assertSame('operador@hotel.es', $contribution->contactEmail());
        $this->assertSame('Ana', $contribution->contactName());
        $this->assertSame(Locale::English, $contribution->contactLocale());
        $this->assertSame($footprint->year(), $contribution->year());
        $this->assertSame(64, strlen($contribution->token()));

        $userId = $contribution->userId();
        $this->assertInstanceOf(UserId::class, $userId);
        $technicalUser = $this->userRepository->find($userId);
        $this->assertNotNull($technicalUser);
        $this->assertTrue($technicalUser->hasRole(Role::Provider));
        $this->assertSame(self::ACCOUNT_ID, (string) $technicalUser->accountId());
        $this->assertSame('hotel-franquiciado-operator', $technicalUser->username());

        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionRegistered));
    }

    public function testALeasedOutPropertyIsADownstreamLeasedAsset(): void
    {
        $footprint = $this->companyFootprint();
        $this->property('leased-out-1', OperatingRegime::LeasedOut);

        $response = $this->createSUT()($this->request($footprint, 'leased-out-1'), $this->owner());

        $this->assertSame(Source::DownstreamLeasedAssets, $this->contributionRepository->find($response->contributionId)?->source());
    }

    public function testTheUsernameGetsASuffixWhenItExists(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('hotel-franquiciado-operator', Role::Provider, new AccountId(self::ACCOUNT_ID)));
        $footprint = $this->companyFootprint();
        $this->property();

        $response = $this->createSUT()($this->request($footprint), $this->owner());

        $userId = $this->contributionRepository->find($response->contributionId)?->userId();
        $this->assertNotNull($userId);
        $this->assertSame('hotel-franquiciado-operator-2', $this->userRepository->find($userId)?->username());
    }

    public function testADifferentContactIsSavedInTheProperty(): void
    {
        $footprint = $this->companyFootprint();
        $this->property(contact: OperatorContact::from('antiguo@hotel.es', null, null));

        $this->createSUT()($this->request($footprint), $this->owner());

        $property = $this->propertyRepository->find(new PropertyId('franchised-1'));
        $this->assertNotNull($property);
        $this->assertSame('operador@hotel.es', $property->operatorContact()->email());
        $this->assertSame('Ana', $property->operatorContact()->name());
        $this->assertSame(Locale::English, $property->operatorContact()->locale());
        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof PropertyOperatorContactUpdated));
    }

    public function testTheSameContactDoesNotUpdateTheProperty(): void
    {
        $footprint = $this->companyFootprint();
        $this->property(contact: OperatorContact::from('operador@hotel.es', 'Ana', Locale::English));

        $this->createSUT()($this->request($footprint), $this->owner());

        $this->assertEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof PropertyOperatorContactUpdated));
    }

    public function testAConsolidatedPropertyIsRejected(): void
    {
        $footprint = $this->companyFootprint();
        $this->property('owned-1', OperatingRegime::Owned);

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request($footprint, 'owned-1'), $this->owner());
    }

    public function testASecondInvitationIsRejected(): void
    {
        $footprint = $this->companyFootprint();
        $this->property();
        $this->createSUT()($this->request($footprint), $this->owner());

        $this->expectException(OperatorContributionAlreadyExistsException::class);

        $this->createSUT()($this->request($footprint), $this->owner());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $footprint = $this->completedCompanyFootprint();
        $this->property();

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()($this->request($footprint), $this->owner());
    }

    public function testAdminCannotInvite(): void
    {
        $footprint = $this->companyFootprint();
        $this->property();

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request($footprint), UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, new AccountId(self::ACCOUNT_ID)));
    }

    public function testAConsultantSupervisingTheAccountCanInvite(): void
    {
        $footprint = $this->companyFootprint();
        $this->property();
        $currentAccount = new StubCurrentAccountIdProvider(new AccountId(self::ACCOUNT_ID));
        $impersonation = new StubCurrentImpersonationProvider(true);
        $this->companyCarbonFootprintAuthorizer = new CompanyCarbonFootprintAuthorizer($currentAccount, null, $impersonation);
        $this->authorizer = new OperatorContributionAuthorizer($currentAccount, null, $impersonation);
        $consultant = UserMother::fromUsernameAndRoleAndAccount('consultant', Role::Owner, new AccountId('consultant-account'));

        $response = $this->createSUT(
            new UserAuthorizer($currentAccount, null, $impersonation),
            new PropertyAuthorizer($currentAccount, null, $impersonation),
        )($this->request($footprint), $consultant);

        $contribution = $this->contributionRepository->find($response->contributionId);
        $this->assertNotNull($contribution);
        $userId = $contribution->userId();
        $this->assertInstanceOf(UserId::class, $userId);
        $technicalUser = $this->userRepository->find($userId);
        $this->assertNotNull($technicalUser);
        $this->assertTrue($technicalUser->hasRole(Role::Provider));
        $this->assertSame(self::ACCOUNT_ID, (string) $technicalUser->accountId());
        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionRegistered));
    }

    public function testAFailedTechnicalUserRegistrationLeavesNoContributionNorEmail(): void
    {
        $footprint = $this->companyFootprint();
        $this->property();
        $actors = UserRepositoryMother::inMemory();
        $actors->add(UserMother::fromUsernameAndRole(SystemActorResolver::SYSTEM_USERNAME, Role::User));
        $this->systemActor = SystemActorResolverMother::from($actors);

        try {
            $this->createSUT()($this->request($footprint), $this->owner());
            $this->fail('The technical user registration should fail');
        } catch (UnauthorizedActionException) {
        }

        $this->assertNull($this->contributionRepository->findActiveByCompanyCarbonFootprintAndProperty($footprint->id(), new PropertyId('franchised-1')));
        $this->assertEmpty(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof OperatorContributionRegistered));
    }

    private function request(CompanyCarbonFootprint $footprint, string $propertyId = 'franchised-1'): RegisterOperatorContributionRequest
    {
        return new RegisterOperatorContributionRequest(
            companyCarbonFootprintId: $footprint->id(),
            propertyId: new PropertyId($propertyId),
            contactEmail: 'operador@hotel.es',
            contactName: 'Ana',
            contactLocale: Locale::English,
        );
    }

    private function createSUT(?UserAuthorizer $userAuthorizer = null, ?PropertyAuthorizer $propertyAuthorizer = null): RegisterOperatorContributionService
    {
        $userAuthorizer ??= new UserAuthorizer();

        return new RegisterOperatorContributionService(
            $this->companyCarbonFootprintRepository,
            $this->companyCarbonFootprintAuthorizer,
            $this->authorizer,
            $this->contributionRepository,
            new CompanyProperties($this->propertyRepository),
            new UpdatePropertyOperatorContactService($this->propertyRepository, $propertyAuthorizer ?? new PropertyAuthorizer()),
            new RegisterUserService($this->userRepository, new Md5PasswordHasher(), $userAuthorizer),
            new GrantRoleService($this->userRepository, $userAuthorizer),
            $this->userRepository,
            $this->systemActor,
        );
    }
}
