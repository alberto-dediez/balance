<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\FileStorage;

use App\Security\Domain\Model\SignatureRequest\SignatureFileStorage;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;

class LocalSignatureFileStorage implements SignatureFileStorage
{
    public function __construct(private string $uploadsDir) {}

    public function getSignedDocumentPath(SignatureRequestId $id): string
    {
        $dir = $this->uploadsDir . '/signatures/' . (string) $id;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/signed.pdf';
    }
}
