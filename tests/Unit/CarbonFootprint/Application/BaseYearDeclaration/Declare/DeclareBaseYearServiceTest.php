<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\BaseYearDeclaration\Declare;

use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearRequest;
use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearService;
use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectResolver;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearNotCompletedException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryBaseYearDeclarationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class DeclareBaseYearServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $footprints;
    private InMemoryBaseYearDeclarationRepository $declarations;
    private DeclareBaseYearService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $properties = PropertyRepositoryMother::inMemory();
        $properties->add(PropertyMother::fromNameAndAccountAndId('Hotel Mar', 'account-1', 'property-1'));

        $this->footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->declarations = BaseYearDeclarationRepositoryMother::inMemory();

        $this->sut = new DeclareBaseYearService(
            new KpiSubjectResolver($properties, CompanyRepositoryMother::inMemory()),
            new SubjectFootprints($this->footprints, CompanyCarbonFootprintRepositoryMother::inMemory()),
            $this->declarations,
            new CarbonFootprintKpiAuthorizer(),
        );
    }

    public function testYearWithoutCompletedFootprintThrows(): void
    {
        $this->footprints->add(PropertyCarbonFootprintMother::from('property-1', 'footprint-1', 'account-1', 2024));

        $this->expectException(BaseYearNotCompletedException::class);

        ($this->sut)($this->request(2024), $this->owner());
    }

    public function testDeclaresTheBaseYearWithTheSnapshotOfItsTotal(): void
    {
        $this->addCompleted('footprint-1', 2024, '1200.5');

        $response = ($this->sut)($this->request(2024, IntensityIndicator::PerStay), $this->owner());

        $this->assertSame(2024, $response->declaration->year());
        $this->assertSame('1200.5', $response->declaration->baseYearTotalKgCo2eq());
        $this->assertSame(IntensityIndicator::PerStay, $response->declaration->primaryIndicator());
        $this->assertSame($response->declaration, $this->declarations->findBySubject(CarbonFootprintReportSubjectType::Property, 'property-1'));
    }

    public function testRedeclaringUpdatesTheSnapshot(): void
    {
        $this->addCompleted('footprint-1', 2023, '900');
        $this->addCompleted('footprint-2', 2024, '1200');
        $first = ($this->sut)($this->request(2023), $this->owner());

        $second = ($this->sut)($this->request(2024), $this->owner());

        $this->assertSame($first->declaration, $second->declaration);
        $this->assertSame(2024, $second->declaration->year());
        $this->assertSame('1200', $second->declaration->baseYearTotalKgCo2eq());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $this->addCompleted('footprint-1', 2024, '1200');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request(2024), UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('account-2')));
    }

    private function addCompleted(string $id, int $year, string $total): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', $id, 'account-1', $year);
        $footprint->complete(new Emissions($total, $total));
        $this->footprints->add($footprint);
    }

    private function request(int $year, ?IntensityIndicator $primaryIndicator = null): DeclareBaseYearRequest
    {
        return new DeclareBaseYearRequest(
            subjectType: CarbonFootprintReportSubjectType::Property,
            subjectId: 'property-1',
            year: $year,
            justification: 'Primer año con inventario completo',
            recalculationThresholdPercent: '5',
            primaryIndicator: $primaryIndicator,
        );
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));
    }
}
