<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Generate;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;

class CertificateLogoDownloader
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaStorage $storage,
    ) {}

    public function download(?MediaId $mediaId): ?string
    {
        if (!$mediaId instanceof MediaId) {
            return null;
        }

        $media = $this->mediaRepository->find($mediaId);

        if (!$media instanceof Media || !$this->storage->exists($media->path())) {
            return null;
        }

        $localPath = sprintf('%s/%s.%s', sys_get_temp_dir(), uniqid('certificate-logo-', true), $media->extension());

        $target = fopen($localPath, 'wb');

        if (false === $target) {
            return null;
        }

        try {
            stream_copy_to_stream($this->storage->read($media->path()), $target);
        } finally {
            fclose($target);
        }

        return $localPath;
    }
}
