<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

interface QrCodeGenerator
{
    public function generate(ContainerId $containerId): QrCode;
}
