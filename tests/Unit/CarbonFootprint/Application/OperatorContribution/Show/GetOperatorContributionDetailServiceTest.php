<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\OperatorContribution\Show\GetOperatorContributionDetailRequest;
use App\CarbonFootprint\Application\OperatorContribution\Show\GetOperatorContributionDetailService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class GetOperatorContributionDetailServiceTest extends OperatorContributionServiceTestCase
{
    public function testReturnsTheSectionsAndTheSummary(): void
    {
        $this->property();
        $contribution = $this->contribution($this->companyFootprint());
        $contribution->markAsPrecalculated();
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 2000.0);
        $contribution->answer();

        $response = $this->createSUT()(new GetOperatorContributionDetailRequest($contribution->id()), $this->owner());

        $keys = static fn(array $sections): array => array_map(static fn(StageSection $section): string => $section->definition->key, $sections);

        $this->assertSame($contribution, $response->contribution);
        $this->assertSame('Hotel Franquiciado', $response->property?->name());
        $this->assertSame(['fixed_facilities_reported', 'own_vehicles_reported', 'refrigerant_leaks_reported'], $keys($response->scope1Sections));
        $this->assertSame(['building_electricity_reported'], $keys($response->scope2Sections));
        $this->assertCount(1, $response->scope1Sections[0]->activities);
        $this->assertSame(2000.0, (float) $response->summary->scope1->co2eq());
        $this->assertSame(2000.0, (float) $response->summary->total->co2eq());
    }

    public function testOwnerOfAnotherAccountCannotView(): void
    {
        $contribution = $this->contribution($this->companyFootprint());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new GetOperatorContributionDetailRequest($contribution->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account')));
    }

    private function createSUT(): GetOperatorContributionDetailService
    {
        return new GetOperatorContributionDetailService(
            $this->contributionRepository,
            $this->companyCarbonFootprintRepository,
            $this->companyCarbonFootprintAuthorizer,
            $this->authorizer,
            $this->sectionsBuilder,
            $this->emissionsCalculator,
            $this->propertyRepository,
        );
    }
}
