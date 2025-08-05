<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrCodeGenerator;

class FakeQrCodeGenerator implements QrCodeGenerator
{
    public function generate(ContainerId $containerId): QrCode
    {
        return new QrCode(
            (string) $containerId
        );
    }
}