<?php

namespace App\Waste\Application\Container\PrintQr;

use App\Waste\Domain\Model\Container\ContainerId;

class PrintQrContainerRequest
{
    public function __construct(public ContainerId $containerId)
    {
        
    }
}