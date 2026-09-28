<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Register;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMaterialityQuestionResponse implements CommandResponse
{
    public function __construct(
        public MaterialityQuestionId $materialityQuestionId,
        public string $text,
    ) {}
}
