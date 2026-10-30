<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\List;

use App\CarbonFootprint\Application\OperatorContribution\List\ListOperatorContributionsRequest;
use App\CarbonFootprint\Application\OperatorContribution\List\ListOperatorContributionsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class ListOperatorContributionsServiceTest extends OperatorContributionServiceTestCase
{
    public function testIndexesTheActiveContributionsByProperty(): void
    {
        $footprint = $this->companyFootprint();
        $first = $this->contribution($footprint, 'franchised-1');
        $this->addActivity($first, Scope::Scope2, Source::BuildingElectricity, 800.0);
        $removed = $this->contribution($footprint, 'franchised-2');
        $removed->remove();
        $this->contribution($this->companyFootprint(), 'franchised-3');

        $response = $this->createSUT()(new ListOperatorContributionsRequest($footprint->id()), $this->owner());

        $this->assertSame(['franchised-1'], array_keys($response->summariesByProperty));
        $this->assertSame(800.0, (float) $response->summariesByProperty['franchised-1']->scope2->co2eq());
    }

    public function testAdminCannotList(): void
    {
        $footprint = $this->companyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListOperatorContributionsRequest($footprint->id()), UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, new AccountId(self::ACCOUNT_ID)));
    }

    private function createSUT(): ListOperatorContributionsService
    {
        return new ListOperatorContributionsService(
            $this->companyCarbonFootprintRepository,
            $this->companyCarbonFootprintAuthorizer,
            $this->authorizer,
            $this->contributionRepository,
            $this->emissionsCalculator,
        );
    }
}
