<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Model\Filter;

class MaterialityQuestionFilter
{
    use Filter;

    public function __construct(
        public ?string $text = null,
        public ?MaterialityCriterion $criterion = null,
        public ?MaterialityTopicId $topicId = null,
        public ?MaterialitySectorId $sectorId = null,
        public bool $onlyGeneric = false,
        public ?bool $active = null,
        public bool $includeRemoved = false,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
