<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Remove;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Shared\Application\CommandBus\Command;

class RemoveProjectRequest implements Command
{
    public function __construct(public ProjectId $projectId) {}
}
