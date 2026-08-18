<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee\List;

use App\Payments\Application\Payee\List\ListPayeesRequest;
use App\Payments\Application\Payee\List\ListPayeesService;
use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPayeesServiceTest extends TestCase
{
    private InMemoryPayeeRepository $payees;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payees = PayeeRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItListsAllPayees(): void
    {
        $this->payees->add(PayeeMother::random());
        $this->payees->add(PayeeMother::random());

        $response = $this->service()(
            new ListPayeesRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(2, $response->totalResults);
        $this->assertCount(2, $response->payees);
    }

    public function testItFiltersByType(): void
    {
        $this->payees->add(PayeeMother::random());
        $this->payees->add(PayeeMother::ready(type: PayeeType::Project));

        $response = $this->service()(
            new ListPayeesRequest(type: PayeeType::Project, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $response->totalResults);
        $this->assertCount(1, $response->payees);
    }

    public function testItPaginatesResults(): void
    {
        $this->payees->add(PayeeMother::random());
        $this->payees->add(PayeeMother::random());
        $this->payees->add(PayeeMother::random());

        $response = $this->service()(
            new ListPayeesRequest(limit: 2),
            UserMother::adminUser(),
        );

        $this->assertCount(2, $response->payees);
        $this->assertSame(3, $response->totalResults);
    }

    public function testAnOwnerCannotListPayees(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ListPayeesRequest(limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): ListPayeesService
    {
        return new ListPayeesService($this->payees, new PayeeAuthorizer());
    }
}
