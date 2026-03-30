<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

interface QrPdfGenerator
{
    /**
     * @return string $path
     */
    public function generate(Container $container, QrCode $qrCode): string;
}
