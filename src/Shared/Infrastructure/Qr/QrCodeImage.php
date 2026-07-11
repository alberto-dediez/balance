<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Qr;

class QrCodeImage
{
    public function __construct(
        public string $path,
    ) {}
}
