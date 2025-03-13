<?php

namespace App\Waste\Application\Container\Remove;

use App\Waste\Domain\Model\Container\ContainerId;

class RemoveContainerResponse
{
    public function __construct(public ContainerId $containerId)
    {
        
    }
}