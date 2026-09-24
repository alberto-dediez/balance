<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\InvalidYouTubeUrlException;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use PHPUnit\Framework\TestCase;

class YouTubeVideoTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validUrls(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ?t=10'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ&list=x'],
            'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'vimeo' => ['https://vimeo.com/123'],
            'home' => ['https://www.youtube.com/'],
            'short id' => ['https://www.youtube.com/watch?v=corto'],
            'javascript' => ['javascript:alert(1)'],
            'empty' => [''],
        ];
    }

    /**
     * @dataProvider validUrls
     */
    public function testFromUrlExtractsTheVideoId(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', YouTubeVideo::fromUrl($url)->id);
    }

    /**
     * @dataProvider invalidUrls
     */
    public function testFromUrlRejectsInvalidUrls(string $url): void
    {
        $this->expectException(InvalidYouTubeUrlException::class);

        YouTubeVideo::fromUrl($url);
    }

    public function testIsValidUrl(): void
    {
        $this->assertTrue(YouTubeVideo::isValidUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertFalse(YouTubeVideo::isValidUrl('https://vimeo.com/123'));
    }

    public function testEmbedAndWatchUrls(): void
    {
        $video = YouTubeVideo::fromId('dQw4w9WgXcQ');

        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->embedUrl());
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $video->watchUrl());
        $this->assertSame('dQw4w9WgXcQ', (string) $video);
    }

    public function testFromIdRejectsAnIdWithTenCharacters(): void
    {
        $this->expectException(InvalidYouTubeUrlException::class);

        YouTubeVideo::fromId('dQw4w9WgXc');
    }
}
