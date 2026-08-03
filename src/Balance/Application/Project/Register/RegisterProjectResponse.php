<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Register;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterProjectResponse implements CommandResponse
{
    public function __construct(
        public ProjectId $projectId,
        public string $name,
    ) {}
}
