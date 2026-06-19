<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Writer;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfExportWriter implements ExportWriter
{
    use BrandedPdfBuilderTrait;

    private ?string $path = null;

    private ?string $title = null;

    /** @var array<int, scalar|null>|null */
    private ?array $headerCells = null;

    /** @var array<int, array<int, scalar|null>> */
    private array $rows = [];

    public function __construct(private readonly TranslatorInterface $translator) {}

    public function open(string $path, ExportationFormat $format, ?string $title = null): void
    {
        $this->path = $path;
        $this->title = $title;
        $this->headerCells = null;
        $this->rows = [];
    }

    public function writeRow(array $cells): void
    {
        if (null === $this->path) {
            throw new NotExportedException('Writer has not been opened');
        }

        if (null === $this->headerCells) {
            $this->headerCells = array_values($cells);

            return;
        }

        $this->rows[] = array_values($cells);
    }

    public function close(): void
    {
        $path = $this->path;

        if (null === $path) {
            return;
        }

        $title = $this->title ?? $this->translator->trans('Datos exportados');
        $dateFormatted = (new \DateTimeImmutable())->format('d / m / Y');

        $pdf = $this->createBrandedPDF($title);

        $pdf->AddPage();

        $this->renderBrandedTitle($pdf, $title, $dateFormatted);

        $this->setBrandedFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML($this->buildBrandedTableHtml($this->headerCells ?? [], $this->rows), true, false, true, false, '');

        $this->renderBrandedChromeOnEveryPage($pdf, mb_strtoupper($title));

        $pdf->Output($path, 'F');

        $this->path = null;
        $this->title = null;
        $this->headerCells = null;
        $this->rows = [];
    }
}
