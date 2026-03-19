<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Infrastructure\QrCode\EndroidQrCodeGenerator;

class EndroidQrCodeGeneratorTest extends TestCase
{
    public function testGenerateReturnsQrCode(): void
    {
        $containerId = ContainerId::generate();

        $sut = $this->createSUT();

        $qrCode = $sut->generate($containerId);

        $this->assertNotNull($qrCode);
        $this->assertTrue($qrCode instanceof QrCode);
        $this->assertNotEmpty($qrCode->path());
    }

    private function createSUT(): EndroidQrCodeGenerator
    {
        return new EndroidQrCodeGenerator();
    }
}