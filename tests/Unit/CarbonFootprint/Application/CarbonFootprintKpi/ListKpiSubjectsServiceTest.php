<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectOption;
use App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects\ListKpiSubjectsRequest;
use App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects\ListKpiSubjectsService;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

final class ListKpiSubjectsServiceTest extends TestCase
{
    public function testListsOnlySubjectsOfTheCurrentAccountWithCompletedFootprints(): void
    {
        DomainEventPublisher::instance()->reset();

        $properties = PropertyRepositoryMother::inMemory();
        $properties->add(PropertyMother::fromNameAndAccountAndId('Hotel Sol', 'account-1', 'p-1'));
        $properties->add(PropertyMother::fromNameAndAccountAndId('Hotel Luna', 'account-1', 'p-2'));
        $properties->add(PropertyMother::fromNameAndAccountAndId('Hotel Ajeno', 'account-2', 'p-3'));

        $footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        foreach ([['p-1', 'f-1', 'account-1', 2025], ['p-1', 'f-2', 'account-1', 2023], ['p-3', 'f-3', 'account-2', 2025]] as [$propertyId, $id, $account, $year]) {
            $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered($propertyId, $id, $account, $year);
            $footprint->complete(new Emissions('100', '100'));
            $footprints->add($footprint);
        }
        $footprints->add(PropertyCarbonFootprintMother::from('p-2', 'f-4', 'account-1', 2025));

        $sut = new ListKpiSubjectsService(
            new StubCurrentAccountIdProvider(new AccountId('account-1')),
            $properties,
            CompanyRepositoryMother::inMemory(),
            new SubjectFootprints($footprints, CompanyCarbonFootprintRepositoryMother::inMemory()),
            new CarbonFootprintKpiAuthorizer(),
        );

        $response = $sut(new ListKpiSubjectsRequest(CarbonFootprintReportSubjectType::Property), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1')));

        $this->assertSame(['p-1'], array_map(static fn(KpiSubjectOption $option): string => $option->id, $response->options));
        $this->assertSame([2023, 2025], $response->options[0]->completedYears);
    }

    public function testAdminIsDenied(): void
    {
        $sut = new ListKpiSubjectsService(
            new StubCurrentAccountIdProvider(null),
            PropertyRepositoryMother::inMemory(),
            CompanyRepositoryMother::inMemory(),
            new SubjectFootprints(PropertyCarbonFootprintRepositoryMother::inMemory(), CompanyCarbonFootprintRepositoryMother::inMemory()),
            new CarbonFootprintKpiAuthorizer(),
        );

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListKpiSubjectsRequest(CarbonFootprintReportSubjectType::Property), UserMother::adminUser());
    }
}
