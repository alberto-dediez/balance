<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\AssignAccount;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\BulkPropertyRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class AssignAccountTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCommandsAreEmptyWithoutElements(): void
    {
        $sut = new AssignAccount();

        $this->assertSame([], $sut->commands($this->bulkRequest($sut, [], AccountId::generate())));
    }

    public function testAssigningAccountBuildsCompleteUpdateRequests(): void
    {
        $accountId = AccountId::generate();
        $companyId = CompanyId::generate();

        $property = PropertyMother::from(PropertyId::generate(), 'Hotel', $accountId, companyId: $companyId, numberOfEmployees: 25, dailyClients: 300);

        $sut = new AssignAccount();

        $commands = $sut->commands($this->bulkRequest($sut, [$property], $accountId));

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(UpdatePropertyRequest::class, $commands[0]);
        $this->assertTrue($property->id()->equals($commands[0]->propertyId));
        $this->assertSame('Hotel', $commands[0]->name);
        $this->assertNotNull($commands[0]->accountId);
        $this->assertTrue($accountId->equals($commands[0]->accountId));
        $this->assertNotNull($commands[0]->companyId);
        $this->assertTrue($companyId->equals($commands[0]->companyId));
        $this->assertSame(25, $commands[0]->numberOfEmployees);
        $this->assertSame(300, $commands[0]->dailyClients);
    }

    public function testEmptyAccountDropsCompanyAndKeepsEmployeesAndDailyClients(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel', AccountId::generate(), companyId: CompanyId::generate(), numberOfEmployees: 25, dailyClients: 300);

        $sut = new AssignAccount();

        $commands = $sut->commands($this->bulkRequest($sut, [$property], null));

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(UpdatePropertyRequest::class, $commands[0]);
        $this->assertNull($commands[0]->accountId);
        $this->assertNull($commands[0]->companyId);
        $this->assertSame(25, $commands[0]->numberOfEmployees);
        $this->assertSame(300, $commands[0]->dailyClients);
    }

    public function testIgnoresNullElements(): void
    {
        $property = PropertyMother::random();

        $sut = new AssignAccount();

        $commands = $sut->commands($this->bulkRequest($sut, [null, $property], AccountId::generate()));

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(UpdatePropertyRequest::class, $commands[0]);
        $this->assertTrue($property->id()->equals($commands[0]->propertyId));
    }

    public function testSuccessMessages(): void
    {
        $property = PropertyMother::random();

        $sut = new AssignAccount();

        [$assign] = $sut->commands($this->bulkRequest($sut, [$property], AccountId::generate()));
        [$release] = $sut->commands($this->bulkRequest($sut, [$property], null));

        $this->assertSame('Se ha asignado la cuenta seleccionada a la propiedad {id}', $sut->success($assign)->getMessage());
        $this->assertSame('Se ha eliminado la cuenta de la propiedad {id}', $sut->success($release)->getMessage());
    }

    /** @param array<int, mixed> $elements */
    private function bulkRequest(AssignAccount $action, array $elements, ?AccountId $accountId): BulkPropertyRequest
    {
        $request = new BulkPropertyRequest();
        $request->elements = $elements;
        $request->action = $action;
        $request->accountId = $accountId;

        return $request;
    }
}
