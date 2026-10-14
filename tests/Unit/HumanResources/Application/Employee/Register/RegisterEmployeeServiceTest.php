<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\Register;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Application\Employee\Register\RegisterEmployeeRequest;
use App\HumanResources\Application\Employee\Register\RegisterEmployeeService;
use App\HumanResources\Domain\Model\Employee\EmployeeRegistered;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterEmployeeServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterEmployeeRequest(
            name: 'John Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            accountId: new AccountId('account-id-test'),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->employeeId);
        $this->assertEquals(36, strlen((string) $result->employeeId));
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);

        $employeePersisted = $repository->find($result->employeeId);

        $this->assertNotNull($employeePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmployeeRegistered);
        $this->assertEquals('John Doe', $lastEvent->name());
        $this->assertEquals('john@example.com', $lastEvent->email());
        $this->assertTrue($employeePersisted->id()->equals($lastEvent->employeeId()));
    }

    public function testIsHandled(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterEmployeeRequest(
            name: 'test',
            email: 'test@example.com',
            accountId: new AccountId('account-id-test'),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EmployeeRepository $repository): RegisterEmployeeService
    {
        return new RegisterEmployeeService(
            employeeRepository: $repository,
            authorizer: new EmployeeAuthorizer(),
        );
    }
}
