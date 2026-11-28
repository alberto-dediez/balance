<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityService;
use App\CarbonFootprint\Application\ContestEntry\ContestEntryConfirmer;
use App\CarbonFootprint\Application\ContestEntry\ContestPrizeReassigner;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Infrastructure\Contest\InMemoryContestWinnerPicker;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Lock\InMemoryContestLock;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Lock\InMemoryContestPublicationLock;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryContestEntryRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryContestRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Persistence\InMemory\Transaction\InMemoryTransactionalSession;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

abstract class ContestApplicationTestCase extends TestCase
{
    protected const string ACCOUNT = 'account-1';
    protected const string OTHER_ACCOUNT = 'account-2';
    protected const string ACCOUNT_WITHOUT_CONTESTS = 'account-3';

    protected InMemoryContestRepository $contestRepository;
    protected InMemoryContestEntryRepository $contestEntryRepository;
    protected InMemoryPropertyRepository $propertyRepository;
    protected InMemoryEventRepository $eventRepository;
    protected InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;
    protected InMemoryAccountRepository $accountRepository;
    protected InMemoryContestLock $contestLock;
    protected InMemoryContestPublicationLock $publicationLock;
    protected InMemoryContestWinnerPicker $winnerPicker;
    protected InMemoryTransactionalSession $transactionalSession;
    protected SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->contestRepository = new InMemoryContestRepository();
        $this->contestEntryRepository = new InMemoryContestEntryRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->contestLock = new InMemoryContestLock();
        $this->publicationLock = new InMemoryContestPublicationLock();
        $this->winnerPicker = new InMemoryContestWinnerPicker();
        $this->transactionalSession = new InMemoryTransactionalSession();

        $this->accountRepository->add(AccountMother::withFeatures('Cuenta', self::ACCOUNT, new AccountFeatures(contests: true)));
        $this->accountRepository->add(AccountMother::withFeatures('Otra cuenta', self::OTHER_ACCOUNT, new AccountFeatures(contests: true)));
        $this->accountRepository->add(AccountMother::withFeatures('Sin concursos', self::ACCOUNT_WITHOUT_CONTESTS, new AccountFeatures()));

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    protected function owner(string $accountId = self::ACCOUNT): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    protected function admin(): User
    {
        return UserMother::adminUser();
    }

    protected function user(string $accountId = self::ACCOUNT): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId($accountId));
    }

    /** @param list<Role>|null $roles */
    protected function authorizer(string $currentAccountId = self::ACCOUNT, ?array $roles = null): ContestAuthorizer
    {
        return new ContestAuthorizer(
            new StubCurrentAccountIdProvider(new AccountId($currentAccountId)),
            new StubCurrentRolesProvider($roles),
            $this->featuresPolicy(),
        );
    }

    protected function featuresPolicy(): AccountFeaturesPolicy
    {
        return new AccountFeaturesPolicy($this->accountRepository);
    }

    protected function hostLocator(string $currentAccountId = self::ACCOUNT, ?PropertyAuthorizer $propertyAuthorizer = null): ContestHostLocator
    {
        $currentAccount = new StubCurrentAccountIdProvider(new AccountId($currentAccountId));
        $propertyAuthorizer ??= new PropertyAuthorizer(currentAccountIdProvider: $currentAccount);

        return new ContestHostLocator(
            $this->propertyRepository,
            $propertyAuthorizer,
            $this->eventRepository,
            new EventAuthorizer($currentAccount),
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer($this->propertyRepository, $propertyAuthorizer, $currentAccount),
        );
    }

    protected function confirmer(): ContestEntryConfirmer
    {
        return new ContestEntryConfirmer($this->contestRepository, $this->contestEntryRepository);
    }

    protected function prizeReassigner(): ContestPrizeReassigner
    {
        return new ContestPrizeReassigner($this->contestRepository, $this->contestEntryRepository, $this->winnerPicker);
    }

    protected function eligibility(): CheckContestEntryEligibilityService
    {
        return new CheckContestEntryEligibilityService($this->contestRepository, $this->contestEntryRepository, $this->featuresPolicy());
    }

    protected function eventHost(string $accountId = self::ACCOUNT, string $name = 'Congreso'): ContestHostReference
    {
        $event = EventMother::fromNameAndAccountAndId($name, $accountId, 'event-' . bin2hex(random_bytes(4)));
        $this->eventRepository->add($event);

        return new ContestHostReference(ContestHostType::Event, (string) $event->id());
    }

    protected function propertyHost(string $accountId = self::ACCOUNT, string $name = 'Hotel'): ContestHostReference
    {
        $property = PropertyMother::fromNameAndAccountAndId($name, $accountId, (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        return new ContestHostReference(ContestHostType::Property, (string) $property->id());
    }

    protected function tripLinkHost(string $accountId = self::ACCOUNT): ContestHostReference
    {
        $link = TripCalculationLinkMother::forAccount($accountId);
        $this->tripCalculationLinkRepository->add($link);

        return new ContestHostReference(ContestHostType::TripCalculationLink, (string) $link->id());
    }

    protected function store(Contest $contest): Contest
    {
        $this->contestRepository->add($contest);

        return $contest;
    }

    protected function storeEntry(ContestEntry $entry): ContestEntry
    {
        $this->contestEntryRepository->add($entry);

        return $entry;
    }

    protected function locale(Locale $locale): void
    {
        FakeLocaleSetter::set($locale);
    }
}
