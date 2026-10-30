<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Ignore;

use App\CarbonFootprint\Application\OperatorContribution\Ignore\IgnoreOperatorContributionRequest;
use App\CarbonFootprint\Application\OperatorContribution\Ignore\IgnoreOperatorContributionService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class IgnoreOperatorContributionServiceTest extends OperatorContributionServiceTestCase
{
    public function testIgnoresTheContribution(): void
    {
        $contribution = $this->contribution($this->companyFootprint());

        $this->createSUT()(new IgnoreOperatorContributionRequest($contribution->id()), $this->owner());

        $this->assertSame(ProviderContributionStatus::Ignored, $this->contributionRepository->find($contribution->id())?->status());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->contribution($this->completedCompanyFootprint());

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()(new IgnoreOperatorContributionRequest($contribution->id()), $this->owner());
    }

    public function testUserCannotIgnore(): void
    {
        $contribution = $this->contribution($this->companyFootprint());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new IgnoreOperatorContributionRequest($contribution->id()), UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID)));
    }

    private function createSUT(): IgnoreOperatorContributionService
    {
        return new IgnoreOperatorContributionService($this->contributionRepository, $this->companyCarbonFootprintRepository, $this->companyCarbonFootprintAuthorizer, $this->authorizer);
    }
}
