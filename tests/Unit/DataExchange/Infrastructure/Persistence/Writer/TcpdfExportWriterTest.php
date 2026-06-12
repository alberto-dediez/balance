<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\Writer;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use App\DataExchange\Infrastructure\Persistence\Writer\TcpdfExportWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfExportWriterTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = (string) tempnam(sys_get_temp_dir(), 'pdf_export_test_');
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }

    public function testItGeneratesAPdfFileWithHeaderAndRows(): void
    {
        $writer = $this->writer();

        $writer->open($this->path, ExportationFormat::Pdf, 'Incidencias');
        $writer->writeRow(['Nombre', 'Fecha y hora', 'Descripción']);
        $writer->writeRow(['Incidencia 1', '12/06/2026 10:30', 'Descripción de la incidencia con un texto algo más largo para comprobar el ajuste de línea.']);
        $writer->writeRow(['Incidencia 2', '12/06/2026 11:45', null]);
        $writer->close();

        self::assertFileExists($this->path);
        self::assertStringStartsWith('%PDF', (string) file_get_contents($this->path));
    }

    public function testItGeneratesAPdfFileWithOnlyTheHeaderRow(): void
    {
        $writer = $this->writer();

        $writer->open($this->path, ExportationFormat::Pdf);
        $writer->writeRow(['Nombre']);
        $writer->close();

        self::assertFileExists($this->path);
        self::assertStringStartsWith('%PDF', (string) file_get_contents($this->path));
    }

    public function testItThrowsWhenWritingBeforeOpening(): void
    {
        $writer = $this->writer();

        $this->expectException(NotExportedException::class);

        $writer->writeRow(['Nombre']);
    }

    private function writer(): TcpdfExportWriter
    {
        $translator = new class implements TranslatorInterface {
            /** @param array<string, mixed> $parameters */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }

            public function getLocale(): string
            {
                return 'es';
            }
        };

        return new TcpdfExportWriter($translator);
    }
}
