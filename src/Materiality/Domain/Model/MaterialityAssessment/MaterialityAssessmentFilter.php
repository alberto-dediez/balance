<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class MaterialityAssessmentFilter
{
    use Filter;

    public function __construct(
        public ?MaterialityAssessmentSubjectType $subjectType = null,
        public ?string $carbonFootprintId = null,
        public ?AccountId $accountId = null,
        public ?int $year = null,
        public ?MaterialityAssessmentStatus $status = null,
        public bool $includeRemoved = false,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
