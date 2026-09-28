<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Translate;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\CommandResponse;

class TranslateMaterialityTopicResponse implements CommandResponse
{
    public function __construct(public MaterialityTopicId $materialityTopicId) {}
}
