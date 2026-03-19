<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Container\ContainerId;

class RemoveContainerRequest implements Command
{
    public function __construct(public ContainerId $containerId) {}
}
