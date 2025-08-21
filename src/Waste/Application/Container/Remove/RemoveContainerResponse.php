<?php

namespace App\Waste\Application\Container\Remove;

use App\Waste\Domain\Model\Container\ContainerId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveContainerResponse implements CommandResponse
{
    public function __construct(public ContainerId $containerId)
    {
        
    }
}