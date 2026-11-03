<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromPropertyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class ClearMaterialityDecisionsFromPropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryPropertyCarbonFootprintRepository();
    }

    public function testClearsTheDecisionsAndWithdrawsTheRecommendedExclusions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $this->repository->add($footprint);

        $response = $this->sut()(new ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest($footprint->id()), $this->owner());

        $this->assertTrue($response->cleared);
        $this->assertSame([], $footprint->materialityDecisions());
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
    }

    public function testAMissingFootprintIsIgnored(): void
    {
        $response = $this->sut()(new ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest(PropertyCarbonFootprintId::generate()), $this->owner());

        $this->assertFalse($response->cleared);
    }

    public function testARemovedFootprintIsIgnored(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->remove();
        $this->repository->add($footprint);

        $response = $this->sut()(new ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest($footprint->id()), $this->owner());

        $this->assertFalse($response->cleared);
        $this->assertCount(1, $footprint->materialityDecisions());
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function sut(): ClearMaterialityDecisionsFromPropertyCarbonFootprintService
    {
        return new ClearMaterialityDecisionsFromPropertyCarbonFootprintService($this->repository, new PropertyCarbonFootprintAuthorizer());
    }
}
