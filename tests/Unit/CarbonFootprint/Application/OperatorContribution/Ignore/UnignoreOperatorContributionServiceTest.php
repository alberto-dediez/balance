<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Ignore;

use App\CarbonFootprint\Application\OperatorContribution\Ignore\UnignoreOperatorContributionRequest;
use App\CarbonFootprint\Application\OperatorContribution\Ignore\UnignoreOperatorContributionService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;

class UnignoreOperatorContributionServiceTest extends OperatorContributionServiceTestCase
{
    public function testUnignoresAnAnsweredContribution(): void
    {
        $contribution = $this->contribution($this->companyFootprint());
        $contribution->answer();
        $contribution->ignore();

        $this->createSUT()(new UnignoreOperatorContributionRequest($contribution->id()), $this->owner());

        $this->assertSame(ProviderContributionStatus::Answered, $this->contributionRepository->find($contribution->id())?->status());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->contribution($this->completedCompanyFootprint());
        $contribution->ignore();

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()(new UnignoreOperatorContributionRequest($contribution->id()), $this->owner());
    }

    private function createSUT(): UnignoreOperatorContributionService
    {
        return new UnignoreOperatorContributionService($this->contributionRepository, $this->companyCarbonFootprintRepository, $this->companyCarbonFootprintAuthorizer, $this->authorizer);
    }
}
