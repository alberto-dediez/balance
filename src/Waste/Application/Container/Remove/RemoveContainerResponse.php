<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Container\ContainerId;

class RemoveContainerResponse implements CommandResponse
{
    public function __construct(public ContainerId $containerId) {}
}
