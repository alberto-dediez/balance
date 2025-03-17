<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrCodeGenerator;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Infrastructure\QrCodeGenerator\EndroidQrCodeGenerator;

class EndroidQrCodeGeneratorTest extends TestCase
{
    public function testGenerateReturnsQrCode()
    {
        $containerId = ContainerId::generate();

        $sut = $this->createSUT();

        $qrCode = $sut->generate($containerId);

        $this->assertNotNull($qrCode);
        $this->assertTrue($qrCode instanceof QrCode);
        $this->assertNotEmpty($qrCode->path());
    }

    private function createSUT()
    {
        return new EndroidQrCodeGenerator();
    }
}