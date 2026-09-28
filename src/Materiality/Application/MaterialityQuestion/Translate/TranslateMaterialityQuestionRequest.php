<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Translate;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class TranslateMaterialityQuestionRequest implements Command
{
    public function __construct(
        public MaterialityQuestionId $materialityQuestionId,
        public string $text,
        public string $help,
        public Locale $locale,
    ) {}
}
