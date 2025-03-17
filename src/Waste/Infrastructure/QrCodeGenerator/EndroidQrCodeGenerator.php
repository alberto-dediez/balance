<?php

namespace App\Waste\Infrastructure\QrCodeGenerator;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrCodeGenerator;

class EndroidQrCodeGenerator implements QrCodeGenerator
{
    public function generate(ContainerId $containerId): QrCode
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: (string) $containerId,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = $builder->build();

        return new QrCode(
            $result->getDataUri()
        );
    }
}