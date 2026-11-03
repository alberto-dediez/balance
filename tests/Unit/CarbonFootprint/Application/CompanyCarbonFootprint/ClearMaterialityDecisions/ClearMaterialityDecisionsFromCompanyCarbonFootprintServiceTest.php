<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class ClearMaterialityDecisionsFromCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryCompanyCarbonFootprintRepository();
    }

    public function testClearsTheDecisionsAndWithdrawsTheRecommendedExclusions(): void
    {
        $footprint = CompanyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $this->repository->add($footprint);

        $response = $this->sut()(new ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest($footprint->id()), $this->owner());

        $this->assertTrue($response->cleared);
        $this->assertSame([], $footprint->materialityDecisions());
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
    }

    public function testAMissingFootprintIsIgnored(): void
    {
        $response = $this->sut()(new ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest(CompanyCarbonFootprintId::generate()), $this->owner());

        $this->assertFalse($response->cleared);
    }

    public function testARemovedFootprintIsIgnored(): void
    {
        $footprint = CompanyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->remove();
        $this->repository->add($footprint);

        $response = $this->sut()(new ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest($footprint->id()), $this->owner());

        $this->assertFalse($response->cleared);
        $this->assertCount(1, $footprint->materialityDecisions());
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function sut(): ClearMaterialityDecisionsFromCompanyCarbonFootprintService
    {
        return new ClearMaterialityDecisionsFromCompanyCarbonFootprintService($this->repository, new CompanyCarbonFootprintAuthorizer());
    }
}
