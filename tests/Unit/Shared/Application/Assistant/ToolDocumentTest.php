<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Assistant;

use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolDocument;
use App\Shared\Application\Assistant\ToolResult;
use PHPUnit\Framework\TestCase;

class ToolDocumentTest extends TestCase
{
    private const int MAX_BYTES = 10485760;

    public function testATextFileIsReturnedAsText(): void
    {
        $document = ToolDocument::from('notas.md', 'text/markdown', '# Notas', self::MAX_BYTES);

        $this->assertSame(['contents' => '# Notas', 'truncated' => false], $document->data());
        $this->assertSame([], $document->attachments());
    }

    public function testALongTextIsTruncated(): void
    {
        $document = ToolDocument::from('largo.txt', 'text/plain', str_repeat('a', ToolResult::TEXT_MAX_LENGTH + 5), self::MAX_BYTES);

        $this->assertTrue($document->truncated);
        $this->assertSame(ToolResult::TEXT_MAX_LENGTH, mb_strlen((string) $document->text));
    }

    public function testAPdfIsAttached(): void
    {
        $document = ToolDocument::from('manual.pdf', 'application/pdf', '%PDF-1.4', self::MAX_BYTES);

        $this->assertSame(['attached' => true], $document->data());
        $this->assertCount(1, $document->attachments());
        $this->assertSame('manual.pdf', $document->attachments()[0]->name);
        $this->assertTrue($document->attachments()[0]->isPdf());
    }

    public function testAnImageIsAttached(): void
    {
        $document = ToolDocument::from('captura.png', 'image/png', 'png-bytes', self::MAX_BYTES);

        $this->assertSame(['attached' => true], $document->data());
        $this->assertTrue($document->attachments()[0]->isImage());
    }

    public function testAnUnreadableTypeIsRejected(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El fichero «hoja.xlsx» no es de un tipo legible');

        ToolDocument::from('hoja.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'x', self::MAX_BYTES);
    }

    public function testAFileLargerThanTheLimitIsRejected(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El fichero «grande.pdf» supera el tamaño máximo');

        ToolDocument::from('grande.pdf', 'application/pdf', '12345', 4);
    }

    public function testReadableTypes(): void
    {
        $this->assertTrue(ToolDocument::isReadable('application/json'));
        $this->assertTrue(ToolDocument::isReadable('image/webp'));
        $this->assertFalse(ToolDocument::isReadable('video/mp4'));
        $this->assertFalse(ToolDocument::isReadable('audio/mpeg'));
    }
}
