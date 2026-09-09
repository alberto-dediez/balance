<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Download;

use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use PHPUnit\Framework\TestCase;

class ContentDispositionTest extends TestCase
{
    public function testAsciiFileNameIsKeptAsIs(): void
    {
        $this->assertSame(
            'attachment; filename=report.pdf',
            ContentDisposition::attachment('report.pdf'),
        );
    }

    public function testInlineDisposition(): void
    {
        $this->assertSame(
            'inline; filename=report.pdf',
            ContentDisposition::inline('report.pdf'),
        );
    }

    public function testAccentedFileNameIsTransliteratedInTheFallback(): void
    {
        $this->assertSame(
            'attachment; filename="Presentacion ano.pdf"; filename*=utf-8\'\'Presentaci%C3%B3n%20a%C3%B1o.pdf',
            ContentDisposition::attachment('Presentación año.pdf'),
        );
    }

    public function testNonLatinFileNameIsTransliteratedInTheFallback(): void
    {
        $this->assertSame(
            'attachment; filename="Otcet 2026.xlsx"; filename*=utf-8\'\'%D0%9E%D1%82%D1%87%D1%91%D1%82%202026.xlsx',
            ContentDisposition::attachment('Отчёт 2026.xlsx'),
        );
    }

    public function testPercentIsReplacedInTheFallback(): void
    {
        $this->assertSame(
            'attachment; filename="100_ cafe.pdf"; filename*=utf-8\'\'100%25%20caf%C3%A9.pdf',
            ContentDisposition::attachment('100% café.pdf'),
        );
    }

    public function testPathSeparatorsAreReplaced(): void
    {
        $this->assertSame(
            'attachment; filename=a_b_c.pdf',
            ContentDisposition::attachment('a/b\\c.pdf'),
        );
    }

    public function testFileNameWithoutAsciiEquivalentFallsBackToDefaultName(): void
    {
        $this->assertSame(
            'attachment; filename=download; filename*=utf-8\'\'%E2%98%85',
            ContentDisposition::attachment('★'),
        );
    }

    public function testEmptyFileNameFallsBackToDefaultName(): void
    {
        $this->assertSame(
            'attachment; filename=download',
            ContentDisposition::attachment(''),
        );
    }
}
