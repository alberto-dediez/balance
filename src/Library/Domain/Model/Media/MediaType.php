<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

enum MediaType: string
{
    case Image = 'Image';
    case Document = 'Document';
    case Video = 'Video';
    case Audio = 'Audio';
    case Other = 'Other';

    public static function fromMimeType(string $mimeType): self
    {
        $mimeType = strtolower($mimeType);

        return match (true) {
            str_starts_with($mimeType, 'image/') => self::Image,
            str_starts_with($mimeType, 'video/') => self::Video,
            str_starts_with($mimeType, 'audio/') => self::Audio,
            in_array($mimeType, self::documentMimeTypes(), true) => self::Document,
            default => self::Other,
        };
    }

    /**
     * @return list<string>
     */
    public static function documentMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/rtf',
            'text/csv',
            'text/plain',
        ];
    }
}
