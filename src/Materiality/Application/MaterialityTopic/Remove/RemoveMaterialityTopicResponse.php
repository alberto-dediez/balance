<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Remove;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMaterialityTopicResponse implements CommandResponse
{
    public function __construct(public MaterialityTopicId $materialityTopicId) {}
}
