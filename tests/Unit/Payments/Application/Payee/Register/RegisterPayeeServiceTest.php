<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee\Register;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Application\Payee\Register\RegisterPayeeRequest;
use App\Payments\Application\Payee\Register\RegisterPayeeService;
use App\Payments\Domain\Model\Payee\DuplicatedPayeeSubjectException;
use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterPayeeServiceTest extends TestCase
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

    public function testItRegistersAReadyPayee(): void
    {
        $accountId = AccountId::generate();

        $response = $this->service()($this->request((string) $accountId), UserMother::adminUser());

        $this->assertSame(PayeeStatus::Ready, $response->status);
        $this->assertNotNull($this->payees->findOneBySubject(PayeeType::Account, (string) $accountId));
    }

    public function testItRefusesTwoPayeesForTheSameSubject(): void
    {
        $accountId = AccountId::generate();

        $this->service()($this->request((string) $accountId), UserMother::adminUser());

        $this->expectException(DuplicatedPayeeSubjectException::class);

        $this->service()($this->request((string) $accountId), UserMother::adminUser());
    }

    public function testItAllowsTheSameSubjectIdForDifferentTypes(): void
    {
        $subjectId = (string) AccountId::generate();

        $this->service()($this->request($subjectId), UserMother::adminUser());
        $this->service()($this->request($subjectId, PayeeType::Project), UserMother::adminUser());

        $this->assertSame(2, $this->payees->totalResultsByFilter(new PayeeFilter(limit: 0)));
    }

    public function testAnOwnerCannotRegisterPayees(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            $this->request((string) AccountId::generate()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function request(string $subjectId, PayeeType $type = PayeeType::Account): RegisterPayeeRequest
    {
        return new RegisterPayeeRequest(
            type: $type,
            subjectId: $subjectId,
            legalName: 'Hotel X SL',
            taxId: 'B12345678',
            iban: 'ES9121000418450200051332',
            holderName: 'Hotel X SL',
        );
    }

    private function service(): RegisterPayeeService
    {
        return new RegisterPayeeService($this->payees, new PayeeAuthorizer());
    }
}
