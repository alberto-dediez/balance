<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrPdfGenerator;

use PHPUnit\Framework\TestCase;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Infrastructure\QrPdfGenerator\TcpdfQrPdfGenerator;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class TcpdfQrPdfGeneratorTest extends TestCase
{
    public function testGenerateReturnsPath()
    {
        $container = ContainerMother::random();

        $sut = $this->createSUT();

        $path = $sut->generate($container, $container->qrCode());

        $this->assertNotNull($path);
        $this->assertNotEmpty($path);
    }

    private function createSUT()
    {
        return new TcpdfQrPdfGenerator(
            translator: new FakeTranslator(),
            wasteRepository: WasteRepositoryMother::inMemory()
        );
    }
}