<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee\View;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Application\Payee\View\ViewPayeeRequest;
use App\Payments\Application\Payee\View\ViewPayeeService;
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

class ViewPayeeServiceTest extends TestCase
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

    public function testItFindsAPayeeById(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $response = $this->service()(
            new ViewPayeeRequest(payeeId: $payee->id()),
            UserMother::adminUser(),
        );

        $this->assertNotNull($response->payee);
        $this->assertTrue($payee->id()->equals($response->payee->id()));
    }

    public function testItFindsAPayeeBySubject(): void
    {
        $subjectId = (string) PayeeMother::random()->id();

        $payee = PayeeMother::ready(subjectId: $subjectId);
        $this->payees->add($payee);

        $response = $this->service()(
            new ViewPayeeRequest(type: PayeeType::Account, subjectId: $subjectId),
            UserMother::adminUser(),
        );

        $this->assertNotNull($response->payee);
        $this->assertTrue($payee->id()->equals($response->payee->id()));
    }

    public function testItReturnsNullWhenNoCriteriaIsGiven(): void
    {
        $response = $this->service()(
            new ViewPayeeRequest(),
            UserMother::adminUser(),
        );

        $this->assertNull($response->payee);
    }

    public function testItReturnsNullWhenThePayeeIsNotFound(): void
    {
        $response = $this->service()(
            new ViewPayeeRequest(payeeId: PayeeMother::random()->id()),
            UserMother::adminUser(),
        );

        $this->assertNull($response->payee);
    }

    public function testAnOwnerCannotViewPayees(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ViewPayeeRequest(payeeId: PayeeMother::random()->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): ViewPayeeService
    {
        return new ViewPayeeService($this->payees, new PayeeAuthorizer());
    }
}
