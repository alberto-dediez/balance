<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Update;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateProjectResponse implements CommandResponse
{
    public function __construct(
        public ProjectId $projectId,
        public string $name,
    ) {}
}
