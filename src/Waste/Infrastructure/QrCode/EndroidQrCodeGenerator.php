<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\QrCodeGenerator;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

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
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return new QrCode(
            $result->getDataUri(),
        );
    }
}
