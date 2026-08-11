<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ProjectContributionFilter
{
    use Filter;

    /** @param list<string>|null $originIds */
    public function __construct(
        public ?AccountId $accountId = null,
        public ?ProjectId $projectId = null,
        public ?ProjectContributionStatus $status = null,
        public ?ProjectContributionOrigin $origin = null,
        public ?string $originId = null,
        public ?array $originIds = null,
        public ?PaymentId $paymentId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public ?\DateTimeImmutable $expiresBefore = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
