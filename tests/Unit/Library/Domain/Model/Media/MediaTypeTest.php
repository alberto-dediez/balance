<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Domain\Model\Media\MediaType;
use PHPUnit\Framework\TestCase;

class MediaTypeTest extends TestCase
{
    /**
     * @dataProvider mimeTypes
     */
    public function testFromMimeType(string $mimeType, MediaType $expected): void
    {
        $this->assertSame($expected, MediaType::fromMimeType($mimeType));
    }

    /**
     * @return array<string, array{string, MediaType}>
     */
    public static function mimeTypes(): array
    {
        return [
            'png' => ['image/png', MediaType::Image],
            'jpeg uppercase' => ['IMAGE/JPEG', MediaType::Image],
            'pdf' => ['application/pdf', MediaType::Document],
            'csv' => ['text/csv', MediaType::Document],
            'mp4' => ['video/mp4', MediaType::Video],
            'mp3' => ['audio/mpeg', MediaType::Audio],
            'unknown' => ['application/octet-stream', MediaType::Other],
        ];
    }
}
