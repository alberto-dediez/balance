<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;

class MaterialityQuestionSectorLink
{
    public function __construct(
        private MaterialityQuestionSectorLinkId $id,
        private MaterialityQuestion $materialityQuestion,
        private MaterialitySectorId $sectorId,
    ) {}

    public function id(): MaterialityQuestionSectorLinkId
    {
        return $this->id;
    }

    public function materialityQuestion(): MaterialityQuestion
    {
        return $this->materialityQuestion;
    }

    public function sectorId(): MaterialitySectorId
    {
        return $this->sectorId;
    }
}
