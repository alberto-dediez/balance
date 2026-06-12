<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Writer;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfExportWriter implements ExportWriter
{
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_LIME_RGB = [193, 209, 24];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_TEAL_RGB = [64, 115, 142];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_BODY_TEXT = [55, 65, 75];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_FAINT_LINE = [220, 225, 230];

    private const HEX_DARK_TEAL = '#1F4858';
    private const HEX_CREAM = '#F4EFE0';
    private const HEX_BODY = '#37414B';
    private const HEX_FAINT_LINE = '#DCE1E6';

    private const FONT_STYLE_REGULAR = 'regular';
    private const FONT_STYLE_MEDIUM = 'medium';
    private const FONT_STYLE_SEMIBOLD = 'semi-bold';

    private const PAGE_WIDTH = 297;
    private const PAGE_HEIGHT = 210;
    private const MARGIN_LEFT = 14;
    private const MARGIN_RIGHT = 14;
    private const MARGIN_TOP = 32;
    private const MARGIN_BOTTOM = 22;

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

        $pdf = $this->createPDF($title);

        $pdf->AddPage();

        $this->renderTitle($pdf, $title, $dateFormatted);

        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML($this->buildTableHtml(), true, false, true, false, '');

        $this->renderChromeOnEveryPage($pdf, mb_strtoupper($title));

        $pdf->Output($path, 'F');

        $this->path = null;
        $this->title = null;
        $this->headerCells = null;
        $this->rows = [];
    }

    private function createPDF(string $title): \TCPDF
    {
        $pdf = new \TCPDF('L');
        $pdf->setCreator('Bioscore Sustainability');
        $pdf->setAuthor('Bioscore Sustainability');
        $pdf->setTitle($title);
        $pdf->setMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(true, self::MARGIN_BOTTOM);

        return $pdf;
    }

    private function renderTitle(\TCPDF $pdf, string $title, string $dateFormatted): void
    {
        $pdf->setXY(16, self::MARGIN_TOP);
        $this->setFont($pdf, 26, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->Cell(180, 12, $title, 0, 0, 'L');

        $this->setFont($pdf, 12, self::FONT_STYLE_REGULAR, self::COLOR_LIME_RGB);
        $pdf->Cell(self::PAGE_WIDTH - self::MARGIN_RIGHT - $pdf->getX() - 2, 12, $dateFormatted, 0, 0, 'R');

        $y = self::MARGIN_TOP + 14;
        $pdf->SetDrawColor(self::COLOR_LIME_RGB[0], self::COLOR_LIME_RGB[1], self::COLOR_LIME_RGB[2]);
        $pdf->SetLineWidth(0.9);
        $pdf->Line(16, $y, 44, $y);
        $pdf->SetDrawColor(self::COLOR_FAINT_LINE[0], self::COLOR_FAINT_LINE[1], self::COLOR_FAINT_LINE[2]);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(44, $y, self::PAGE_WIDTH - self::MARGIN_RIGHT - 2, $y);
        $pdf->setY($y + 6);
    }

    private function buildTableHtml(): string
    {
        $headers = $this->headerCells ?? [];

        $columnWidth = number_format(100 / max(1, count($headers)), 2) . '%';

        $html = '<table cellpadding="5" cellspacing="0"><thead><tr style="background-color:' . self::HEX_DARK_TEAL . ';color:' . self::HEX_CREAM . ';">';

        foreach ($headers as $header) {
            $html .= '<td width="' . $columnWidth . '"><b>' . htmlspecialchars(mb_strtoupper((string) $header)) . '</b></td>';
        }

        $html .= '</tr></thead>';

        foreach ($this->rows as $index => $row) {
            $backgroundColor = 0 === $index % 2 ? '#FFFFFF' : self::HEX_CREAM;

            $html .= '<tr style="background-color:' . $backgroundColor . ';color:' . self::HEX_BODY . ';border-bottom:0.2mm solid ' . self::HEX_FAINT_LINE . ';">';

            foreach ($row as $cell) {
                $html .= '<td width="' . $columnWidth . '">' . nl2br(htmlspecialchars((string) $cell)) . '</td>';
            }

            $html .= '</tr>';
        }

        return $html . '</table>';
    }

    private function renderChromeOnEveryPage(\TCPDF $pdf, string $titleUpper): void
    {
        $pageCount = $pdf->getNumPages();

        for ($page = 1; $page <= $pageCount; ++$page) {
            $pdf->setPage($page);

            // setPage() restores the per-page break state, so it must be disabled after every call
            $pdf->setAutoPageBreak(false, 0);

            $pdf->Rect(0, 26, 2.4, self::PAGE_HEIGHT - 56, 'F', [], self::COLOR_LIME_RGB);

            $pdf->Image($this->getImagePath('logo-dark.png'), self::MARGIN_LEFT, 10, 42, 14, '', '', '', false, 300, '', false, false, 0);

            $pdf->setXY(self::PAGE_WIDTH - self::MARGIN_RIGHT - 120, 14);
            $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
            $pdf->setFontSpacing(0.6);
            $pdf->Cell(120, 5, $titleUpper, 0, 0, 'R');
            $pdf->setFontSpacing(0);

            // Footer: left text
            $pdf->setXY(self::MARGIN_LEFT, self::PAGE_HEIGHT - 15);
            $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
            $pdf->setFontSpacing(0.6);
            $pdf->Cell(140, 5, $titleUpper . ' · BIOSCORE SUSTAINABILITY', 0, 0, 'L');

            // Footer: lime dot + page numbering (right)
            $pdf->Circle(self::PAGE_WIDTH - 36, self::PAGE_HEIGHT - 12.5, 1.2, 0, 360, 'F', [], self::COLOR_LIME_RGB);
            $pdf->setXY(self::PAGE_WIDTH - 33, self::PAGE_HEIGHT - 15);
            $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
            $pdf->Cell(20, 5, $page . ' / ' . $pageCount, 0, 0, 'L');
            $pdf->setFontSpacing(0);
        }

        $pdf->setPage($pageCount);
    }

    /** @param array{0: int, 1: int, 2: int} $color */
    private function setFont(\TCPDF $pdf, int|float $size, string $type, array $color): void
    {
        $fontName = match ($type) {
            self::FONT_STYLE_SEMIBOLD => 'Rubik-SemiBold',
            self::FONT_STYLE_MEDIUM => 'Rubik-Medium',
            default => 'Rubik-Regular',
        };

        $font = \TCPDF_FONTS::addTTFfont($this->getFontPath($fontName), 'TrueTypeUnicode', '', 96);

        assert(is_string($font));

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);
        $pdf->setTextColor($color[0], $color[1], $color[2]);
    }

    private function getRootDir(): string
    {
        return __DIR__ . '/../../../../../';
    }

    private function getFontPath(string $fontName): string
    {
        return sprintf('%sassets/fonts/%s.ttf', $this->getRootDir(), $fontName);
    }

    private function getImagePath(string $filename): string
    {
        return sprintf('%sassets/images/%s', $this->getRootDir(), $filename);
    }
}
