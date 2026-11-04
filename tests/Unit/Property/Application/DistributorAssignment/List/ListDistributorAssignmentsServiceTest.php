<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment\List;

use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsRequest;
use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizerMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ListDistributorAssignmentsServiceTest extends TestCase
{
    private InMemoryDistributorAssignmentRepository $repository;

    private InMemoryPropertyRepository $propertyRepository;

    private AccountId $distributorId;

    private AccountId $hotelId;

    private DistributorAssignment $byAccount;

    private DistributorAssignment $byProperty;

    private DistributorAssignment $foreign;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->distributorId = new AccountId('distributor');
        $this->hotelId = new AccountId('hotel');

        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $this->hotelId);
        $foreignProperty = PropertyMother::from(PropertyId::generate(), 'Foreign 1', new AccountId('foreign'));

        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->propertyRepository->add($property);
        $this->propertyRepository->add($foreignProperty);

        $this->byAccount = DistributorAssignmentMother::forAccount($this->distributorId, $this->hotelId);
        $this->byProperty = DistributorAssignmentMother::forProperty(AccountId::generate(), $property->id());
        $this->foreign = DistributorAssignmentMother::forProperty($this->distributorId, $foreignProperty->id());

        $this->repository = DistributorAssignmentRepositoryMother::inMemory([$this->byAccount, $this->byProperty, $this->foreign]);
    }

    public function testAdminSeesEverything(): void
    {
        $response = $this->createSUT(null)(new ListDistributorAssignmentsRequest(limit: 0), UserMother::adminUser());

        $this->assertSame(3, $response->totalResults);
        $this->assertCount(3, $response->assignments);
    }

    public function testAdminFiltersByDistributor(): void
    {
        $response = $this->createSUT(null)(new ListDistributorAssignmentsRequest(distributorAccountId: $this->distributorId, limit: 0), UserMother::adminUser());

        $this->assertSame(2, $response->totalResults);
    }

    public function testAdminFiltersByAdheredAccountIncludingItsProperties(): void
    {
        $request = new ListDistributorAssignmentsRequest(accountId: $this->hotelId, limit: 0);

        $response = $this->createSUT(null)($request, UserMother::adminUser());

        $ids = array_map(static fn(DistributorAssignment $assignment): string => (string) $assignment->id(), $response->assignments);
        sort($ids);

        $expected = [(string) $this->byAccount->id(), (string) $this->byProperty->id()];
        sort($expected);

        $this->assertSame($expected, $ids);
        $this->assertSame(2, $response->totalResults);
        $this->assertNull($request->propertyIds);
    }

    public function testOwnerSeesAssignmentsOfAccountAndProperties(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->hotelId);

        $response = $this->createSUT($this->hotelId)(new ListDistributorAssignmentsRequest(limit: 0), $owner);

        $ids = array_map(static fn(DistributorAssignment $assignment): string => (string) $assignment->id(), $response->assignments);
        sort($ids);

        $expected = [(string) $this->byAccount->id(), (string) $this->byProperty->id()];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function testOwnerCannotFilterByDistributor(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->hotelId);

        $response = $this->createSUT($this->hotelId)(new ListDistributorAssignmentsRequest(distributorAccountId: $this->distributorId, accountId: new AccountId('foreign'), limit: 0), $owner);

        $this->assertSame(2, $response->totalResults);
    }

    public function testOwnerRequestIsNotMutated(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->hotelId);
        $foreignId = new AccountId('foreign');
        $request = new ListDistributorAssignmentsRequest(distributorAccountId: $this->distributorId, accountId: $foreignId, limit: 0);

        $response = $this->createSUT($this->hotelId)($request, $owner);

        $this->assertSame(2, $response->totalResults);
        $this->assertSame($foreignId, $request->accountId);
        $this->assertSame($this->distributorId, $request->distributorAccountId);
        $this->assertNull($request->propertyIds);
    }

    private function createSUT(?AccountId $currentAccountId): ListDistributorAssignmentsService
    {
        return new ListDistributorAssignmentsService(
            $this->repository,
            $this->propertyRepository,
            DistributorAssignmentAuthorizerMother::create(propertyRepository: $this->propertyRepository),
            new StubCurrentAccountIdProvider($currentAccountId),
        );
    }
}
