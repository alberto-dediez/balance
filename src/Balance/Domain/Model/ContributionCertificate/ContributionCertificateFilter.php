<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ContributionCertificateFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?ProjectId $projectId = null,
        public ?ContributionCertificateType $type = null,
        public ?ContributionCertificateStatus $status = null,
        public ?string $code = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
