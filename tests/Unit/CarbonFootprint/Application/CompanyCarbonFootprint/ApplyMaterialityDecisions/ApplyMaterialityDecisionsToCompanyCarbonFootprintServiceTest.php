<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOmitCommentGenerator;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

final class ApplyMaterialityDecisionsToCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryCompanyCarbonFootprintRepository();
    }

    public function testExcludesTheOmittedSourcesWithAGeneratedComment(): void
    {
        $footprint = CompanyCarbonFootprintMother::withMaterialityAnswered('company-1', 'footprint-1');
        $this->repository->add($footprint);

        $response = $this->sut()(new ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest($footprint->id(), [
            MaterialityDecisionInputMother::omit(5, 38.0),
            MaterialityDecisionInputMother::calculate(1),
        ]), $this->owner());

        $exclusion = $footprint->exclusionFor(Scope::Scope3, Source::OperationsWaste);
        $this->assertNotNull($exclusion);
        $this->assertSame(ExclusionReason::NotSignificant, $exclusion->reason());
        $this->assertSame('Análisis de materialidad: relevancia 38,00 % por debajo del umbral 50 %', $exclusion->comment());
        $this->assertSame(1, $response->excluded);
        $this->assertSame(0, $response->included);
        $this->assertCount(2, $footprint->materialityDecisions());
    }

    public function testACompletedFootprintIsLeftUntouched(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $footprint->complete(new Emissions());
        $this->repository->add($footprint);

        $response = $this->sut()(new ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest($footprint->id(), [MaterialityDecisionInputMother::omit(5)]), $this->owner());

        $this->assertSame(0, $response->excluded);
        $this->assertSame([], $footprint->materialityDecisions());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = CompanyCarbonFootprintMother::withMaterialityAnswered('company-1', 'footprint-1', 'other-account');
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest($footprint->id(), []), $this->owner());
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function sut(): ApplyMaterialityDecisionsToCompanyCarbonFootprintService
    {
        return new ApplyMaterialityDecisionsToCompanyCarbonFootprintService($this->repository, new CompanyCarbonFootprintAuthorizer(), new MaterialityOmitCommentGenerator(new FakeTranslator()));
    }
}
