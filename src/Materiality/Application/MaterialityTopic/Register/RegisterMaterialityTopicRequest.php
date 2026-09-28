<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\Register;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Shared\Application\CommandBus\Command;

class RegisterMaterialityTopicRequest implements Command
{
    public function __construct(
        public ?int $number = null,
        public MaterialityScope $scope = MaterialityScope::Scope3,
        public ?MaterialityTopicDirection $direction = null,
        public string $name = '',
        public string $description = '',
        public int $position = 0,
        public bool $standard = false,
    ) {}
}
