<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\DataExchange;

use App\Balance\Application\ProjectContribution\Export\ProjectContributionsExportFilter;
use App\Balance\Application\ProjectContribution\List\ProjectContributionsListScope;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Balance\Infrastructure\DataExchange\ProjectContributionsExportationType;
use App\Balance\Infrastructure\Symfony\Twig\ProjectExtension;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Infrastructure\Contribution\PropertyTripDistributedContributionsResolverMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\StubAuthorizationChecker;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class ProjectContributionsExportationTypeTest extends TestCase
{
    private Account $account;

    private Account $otherAccount;

    private InMemoryAccountRepository $accountRepository;

    protected function setUp(): void
    {
        $this->account = AccountMother::fromNameAndId('Mi cuenta', 'account-id');
        $this->otherAccount = AccountMother::fromNameAndId('Otra cuenta', 'other-account');
        $this->accountRepository = new InMemoryAccountRepository([
            'account-id' => $this->account,
            'other-account' => $this->otherAccount,
        ]);
    }

    public function testExportsTheContributorPersonalData(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paidCompany($this->account->id()));

        $rows = $this->rows($this->sut($repository), $this->ownerFilter($this->account->id()));

        self::assertCount(1, $rows);
        self::assertSame('Guest', $rows[0]['contributorName']);
        self::assertSame('guest@example.com', $rows[0]['contributorEmail']);
        self::assertSame('ES', $rows[0]['contributorCountry']);
        self::assertSame('Sí', $rows[0]['contributorIsCompany']);
        self::assertSame('Acme SL', $rows[0]['contributorCompanyName']);
        self::assertSame('B12345678', $rows[0]['contributorVatNumber']);
        self::assertSame('Mi cuenta', $rows[0]['account']);
        self::assertSame(24.2, $rows[0]['totalAmount']);
    }

    public function testResolvesTheProjectName(): void
    {
        $project = ProjectMother::fromNameAndId('Bosque Atlántico', 'project-id');
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid($this->account->id(), $project->id()));

        $rows = $this->rows(
            $this->sut($repository, projectExtension: new ProjectExtension($projectRepository)),
            $this->ownerFilter($this->account->id()),
        );

        self::assertSame('Bosque Atlántico', $rows[0]['project']);
    }

    public function testOwnerExportOnlyIncludesTheContributionsOfTheAccount(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid($this->account->id()));
        $repository->add(ProjectContributionMother::paid($this->otherAccount->id()));

        self::assertCount(1, $this->rows($this->sut($repository), $this->ownerFilter($this->account->id())));
    }

    public function testAppliesTheStatusFilter(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid($this->account->id()));
        $repository->add(ProjectContributionMother::pending($this->account->id()));

        $filter = $this->ownerFilter($this->account->id());
        $filter->status = ProjectContributionStatus::Paid;

        self::assertCount(1, $this->rows($this->sut($repository), $filter));
    }

    public function testAdminExportIncludesEveryAccount(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid($this->account->id()));
        $repository->add(ProjectContributionMother::paid($this->otherAccount->id()));

        self::assertCount(2, $this->rows($this->sut($repository), new ProjectContributionsExportFilter()));
    }

    public function testDistributorExportIncludesDistributedContributionsWithoutTheirEmail(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $accountRepository = new InMemoryAccountRepository([
            'distributor' => $distributor,
            'hotel' => $hotel,
        ]);

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid($distributor->id(), origin: ProjectContributionOrigin::EventCarbonFootprint));
        $repository->add(ProjectContributionMother::paid($hotel->id()));
        $repository->add(ProjectContributionMother::paid(AccountId::generate()));

        $resolver = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        );

        $sut = $this->sut(
            $repository,
            accountRepository: $accountRepository,
            listScope: new ProjectContributionsListScope($resolver),
        );

        $rows = $this->rows($sut, $this->ownerFilter($distributor->id()));

        self::assertCount(2, $rows);

        $emails = [];

        foreach ($rows as $row) {
            $emails[(string) $row['account']] = $row['contributorEmail'];
        }

        self::assertSame(['Greenhost' => 'guest@example.com', 'Hotel' => ''], $emails);
    }

    public function testDehydratedFilterIsHydratedBack(): void
    {
        $sut = $this->sut();
        $filter = $this->ownerFilter($this->account->id());
        $filter->status = ProjectContributionStatus::Paid;
        $filter->from = new \DateTimeImmutable('2026-01-01');

        $hydrated = $sut->hydrateFilter($sut->dehydrateFilter($filter));

        self::assertInstanceOf(ProjectContributionsExportFilter::class, $hydrated);
        self::assertSame('account-id', (string) $hydrated->accountId);
        self::assertSame('account-id', (string) $hydrated->viewerAccountId);
        self::assertSame(ProjectContributionStatus::Paid, $hydrated->status);
        self::assertSame('2026-01-01', $hydrated->from?->format('Y-m-d'));
        self::assertSame(0, $hydrated->limit);
    }

    public function testCreatesAFilterFixedToTheCurrentAccountWhenEveryAccountCannotBeExported(): void
    {
        $filter = $this->sut(granted: false)->createFilter();

        self::assertInstanceOf(ProjectContributionsExportFilter::class, $filter);
        self::assertSame('account-id', (string) $filter->accountId);
        self::assertSame('account-id', (string) $filter->viewerAccountId);
    }

    public function testCreatesAFilterWithoutAccountWhenEveryAccountCanBeExported(): void
    {
        $filter = $this->sut(granted: true)->createFilter();

        self::assertInstanceOf(ProjectContributionsExportFilter::class, $filter);
        self::assertNull($filter->accountId);
        self::assertNull($filter->viewerAccountId);
    }

    public function testAuthorizesTheOwnerToExportTheContributionsOfTheCurrentAccount(): void
    {
        self::assertTrue($this->sut()->isAuthorizedForFilter(
            $this->ownerFilter($this->account->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id()),
        ));
    }

    public function testDoesNotAuthorizeAUserWithoutOwnerRole(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(
            $this->ownerFilter($this->account->id()),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $this->account->id()),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAnotherAccount(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(
            $this->ownerFilter($this->otherAccount->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id()),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToWidenTheAccountOfTheFilter(): void
    {
        $filter = $this->ownerFilter($this->account->id());
        $filter->accountId = $this->otherAccount->id();

        self::assertFalse($this->sut()->isAuthorizedForFilter(
            $filter,
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id()),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportEveryAccount(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(
            new ProjectContributionsExportFilter(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id()),
        ));
    }

    public function testAuthorizesAdminsToExportEveryAccount(): void
    {
        self::assertTrue($this->sut()->isAuthorizedForFilter(new ProjectContributionsExportFilter(), UserMother::adminUser()));
    }

    public function testDoesNotAuthorizeAnotherFilterClass(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(new \stdClass(), UserMother::adminUser()));
    }

    private function ownerFilter(AccountId $accountId): ProjectContributionsExportFilter
    {
        $filter = new ProjectContributionsExportFilter(accountId: $accountId);
        $filter->viewerAccountId = $accountId;

        return $filter;
    }

    /** @return list<array<string, scalar|null>> */
    private function rows(ProjectContributionsExportationType $sut, ProjectContributionsExportFilter $filter): array
    {
        $rows = [];

        foreach ($sut->rows($filter, []) as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function sut(
        ?ProjectContributionRepository $repository = null,
        bool $granted = false,
        ?InMemoryAccountRepository $accountRepository = null,
        ?ProjectContributionsListScope $listScope = null,
        ?ProjectExtension $projectExtension = null,
    ): ProjectContributionsExportationType {
        $accountRepository ??= $this->accountRepository;

        return new ProjectContributionsExportationType(
            $repository ?? ProjectContributionRepositoryMother::inMemory(),
            $accountRepository,
            new ProjectContributionAuthorizer(
                currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-id')),
                accountFeaturesPolicy: new AccountFeaturesPolicy($accountRepository),
                distributorPolicy: new DistributorPolicy($accountRepository),
            ),
            $listScope ?? new ProjectContributionsListScope(),
            new DistributorPolicy($accountRepository),
            $projectExtension ?? new ProjectExtension(ProjectRepositoryMother::inMemory()),
            new FakeTranslator(),
            new StubAuthorizationChecker($granted),
            new StubCurrentAccountIdProvider(new AccountId('account-id')),
        );
    }
}
