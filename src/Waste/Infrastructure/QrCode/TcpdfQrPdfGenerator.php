<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\QrPdfGenerator;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Infrastructure\Tcpdf\GenericGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfQrPdfGenerator implements QrPdfGenerator
{
    use GenericGenerator;

    public function __construct(
        private TranslatorInterface $translator,
        private WasteRepository $wasteRepository,
        private PropertyRepository $propertyRepository,
    ) {}

    public function generate(Container $container, QrCode $qrCode): string
    {
        $wasteId = $container->wasteId();
        assert($wasteId instanceof WasteId);
        $propertyId = $container->propertyId();
        assert($propertyId instanceof PropertyId);

        $waste = $this->wasteRepository->find($wasteId);
        $property = $this->propertyRepository->find($propertyId);

        if (!$property instanceof Property) {
            throw new \RuntimeException('Property not found');
        }

        $wasteColor = $this->getBackgroundColor($waste);

        $pdf = $this->createPDF($this->translator->trans('Contenedor {containerId}', [
            '{containerId}' => (string) $container->id(),
        ]));

        $pdf->AddPage();

        // logo bioscore
        $pdf->Image($this->getImagePath('logo-vertical.png'), 76, 12, 65, 60, '', '', '', false, 300, '', false, false, 0);

        // Rectangulo con nombre del residuo
        $pdf->Rect(0, 84, 210, 32, 'DF', ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0], $wasteColor);
        $pdf->setXY(10, 84);
        $this->setFont($pdf, 60, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100);
        $pdf->setFontSpacing(1);
        $pdf->MultiCell(w: 190, h: 30, txt: mb_strtoupper((string) $waste?->name()), border: 0, align: 'C', maxh: 30, valign: 'B', fitcell: true);

        $pdf->setXY(0, 125);
        $this->setFont($pdf, 35, self::FONT_STYLE_SEMIBOLD, $wasteColor);
        $pdf->MultiCell(w: 210, h: 30, txt: $property->name(), border: 0, align: 'C');

        // Capacidad del contenedor
        if ($container->volume() > 0) {
            $pdf->setXY(0, 143);
            $this->setFont($pdf, 30, self::FONT_STYLE_SEMIBOLD, $wasteColor);
            $pdf->MultiCell(w: 210, h: 30, txt: $this->translator->trans('{volume} LITROS', ['{volume}' => number_format(floatval($container->volume()), 0)]), border: 0, align: 'C');
        }

        // Propostio
        $pdf->setXY(0, 160);
        $this->setFont($pdf, 42, self::FONT_STYLE_REGULAR, $wasteColor);
        $pdf->setFontSpacing(1);
        $pdf->MultiCell(w: 210, h: 30, txt: mb_strtoupper($this->translator->trans($container->purpose()->value, [], 'purpose')), border: 0, align: 'C');

        // Fecha de emision
        $pdf->setXY(0, 185);
        $this->setFont($pdf, 24, self::FONT_STYLE_REGULAR, $wasteColor);
        $pdf->MultiCell(w: 105, h: 10, txt: $this->translator->trans('FECHA EMISIÓN QR', []), border: 0, align: 'R');

        $pdf->setXY(115, 185);
        $this->setFont($pdf, 24, self::FONT_STYLE_BOLD, $wasteColor);
        $pdf->MultiCell(w: 100, h: 10, txt: date('d.m.Y', strtotime('NOW')), border: 0, align: 'L');

        // QR
        $image = '/tmp/' . $container->id() . '-qr.png';
        $imageContent = @file_get_contents($qrCode->path());
        file_put_contents($image, $imageContent);
        $pdf->Image($image, 72, 205, 64, 64, '', '', '', false, 300, '', false, false, 0);


        // Firma bioscore
        $pdf->setXY(0, 275);
        $this->setFont($pdf, 16, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
        $pdf->MultiCell(w: 210, h: 30, txt: $this->translator->trans('bioscore.info', []), border: 0, align: 'C');


        $path = '/tmp/' . $container->id() . '.pdf';

        $this->savePDF($pdf, $path);

        return $path;
    }

    /** @return array<int, int> */
    private function getBackgroundColor(?Waste $waste): array
    {
        if (!$waste instanceof Waste || '' === $waste->color()) {
            $color = $this->hex2rgb(self::COLOR_PRIMARY);
            assert(null !== $color);

            /* @var array<int, int> $color */
            return $color;
        }

        $color = $this->hex2rgb($waste->color());
        assert(null !== $color);

        /* @var array<int, int> $color */
        return $color;
    }
}
