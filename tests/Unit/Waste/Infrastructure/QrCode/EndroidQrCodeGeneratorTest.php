<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Infrastructure\QrCode\EndroidQrCodeGenerator;
use PHPUnit\Framework\TestCase;

class EndroidQrCodeGeneratorTest extends TestCase
{
    public function testGenerateReturnsQrCode(): void
    {
        $containerId = ContainerId::generate();

        $sut = $this->createSUT();

        $qrCode = $sut->generate($containerId);

        $this->assertNotEmpty($qrCode->path());
    }

    private function createSUT(): EndroidQrCodeGenerator
    {
        return new EndroidQrCodeGenerator();
    }
}
