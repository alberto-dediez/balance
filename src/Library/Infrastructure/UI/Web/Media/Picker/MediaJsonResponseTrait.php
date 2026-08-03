<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Picker;

use App\Library\Domain\Model\Media\Media;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait MediaJsonResponseTrait
{
    /**
     * @return array<string,mixed>
     */
    private function toJsonElement(Media $media, UrlGeneratorInterface $urlGenerator): array
    {
        return [
            'id' => (string) $media->id(),
            'name' => $media->name(),
            'fileName' => $media->fileName(),
            'type' => $media->type()->value,
            'isImage' => $media->isImage(),
            'mimeType' => $media->mimeType(),
            'size' => $media->size(),
            'url' => $urlGenerator->generate('library_media_show', ['mediaId' => (string) $media->id()]),
        ];
    }
}
