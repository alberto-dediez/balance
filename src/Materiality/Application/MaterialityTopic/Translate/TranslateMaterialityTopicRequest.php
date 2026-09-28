<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Translate;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class TranslateMaterialityTopicRequest implements Command
{
    public function __construct(
        public MaterialityTopicId $materialityTopicId,
        public string $name,
        public string $description,
        public Locale $locale,
    ) {}
}
