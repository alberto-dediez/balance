<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Update;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\Command;

class UpdateMaterialityTopicRequest implements Command
{
    public function __construct(
        public MaterialityTopicId $materialityTopicId,
        public MaterialityScope $scope,
        public ?MaterialityTopicDirection $direction,
        public string $name,
        public string $description,
        public int $position,
        public bool $active,
    ) {}
}
