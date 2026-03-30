<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\PrintQr;

use App\Waste\Domain\Model\Container\ContainerId;

class PrintQrContainerRequest
{
    public function __construct(public ContainerId $containerId) {}
}
