<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class LocatedOrganizationalCarbonFootprint
{
    public function __construct(
        public readonly PropertyCarbonFootprint|CompanyCarbonFootprint $carbonFootprint,
        public readonly CarbonFootprintReportSubjectType $subjectType,
        private readonly AbstractAuthorizer $authorizer,
    ) {}

    public function accountId(): ?AccountId
    {
        return $this->carbonFootprint->accountId();
    }

    public function denyUnlessCanDoThat(SecurityAction $action, User $user): void
    {
        $this->authorizer->denyUnlessCanDoThat($this->carbonFootprint, $action, $user);
    }

    public function ensureIsEditable(): void
    {
        if ($this->carbonFootprint->isCompleted()) {
            throw new OrganizationalProviderContributionClosedException();
        }
    }
}
