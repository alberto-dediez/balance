<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Infrastructure\Storage\LocalManualStorage;
use PHPUnit\Framework\TestCase;

class LocalManualStorageTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/changelog-manuals-test-' . uniqid();
        mkdir($this->basePath, 0o775, true);
        file_put_contents($this->basePath . '/manual.pdf', 'contents');
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/manual.pdf');
        @rmdir($this->basePath);
    }

    public function testExists(): void
    {
        $sut = new LocalManualStorage($this->basePath);

        $this->assertTrue($sut->exists('manual.pdf'));
        $this->assertFalse($sut->exists('missing.pdf'));
    }

    public function testReadReturnsAStreamWithTheContents(): void
    {
        $sut = new LocalManualStorage($this->basePath);

        $stream = $sut->read('manual.pdf');

        $this->assertSame('contents', stream_get_contents($stream));
        fclose($stream);
    }

    public function testReadContentsReturnsTheText(): void
    {
        $sut = new LocalManualStorage($this->basePath);

        $this->assertSame('contents', $sut->readContents('manual.pdf'));
    }

    public function testSizeReturnsTheFileSize(): void
    {
        $sut = new LocalManualStorage($this->basePath);

        $this->assertSame(8, $sut->size('manual.pdf'));
    }

    /**
     * @dataProvider unsafeFileNameProvider
     */
    public function testUnsafeFileNamesAreRejected(string $fileName): void
    {
        $sut = new LocalManualStorage($this->basePath);

        $this->expectException(ManualNotFoundException::class);

        $sut->read($fileName);
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function unsafeFileNameProvider(): iterable
    {
        yield 'slash' => ['sub/manual.pdf'];
        yield 'backslash' => ['sub\\manual.pdf'];
        yield 'parent traversal' => ['../manual.pdf'];
    }
}
