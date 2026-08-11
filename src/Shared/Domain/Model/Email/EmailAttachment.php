<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Email;

final readonly class EmailAttachment
{
    public function __construct(
        public string $filename,
        public string $path,
        public string $contentType = 'application/pdf',
    ) {}
}
