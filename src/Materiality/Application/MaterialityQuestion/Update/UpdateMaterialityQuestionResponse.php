<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Update;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMaterialityQuestionResponse implements CommandResponse
{
    public function __construct(
        public MaterialityQuestionId $materialityQuestionId,
        public string $text,
    ) {}
}
