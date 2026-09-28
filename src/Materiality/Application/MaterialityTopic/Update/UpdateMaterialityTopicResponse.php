<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Update;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMaterialityTopicResponse implements CommandResponse
{
    public function __construct(
        public MaterialityTopicId $materialityTopicId,
        public string $name,
    ) {}
}
