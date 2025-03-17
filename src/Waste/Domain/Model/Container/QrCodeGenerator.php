<?php

namespace App\Waste\Domain\Model\Container;

interface QrCodeGenerator
{
    public function generate(ContainerId $containerId): QrCode;
}