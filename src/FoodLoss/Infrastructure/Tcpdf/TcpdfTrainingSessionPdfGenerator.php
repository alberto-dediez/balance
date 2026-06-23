<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Tcpdf;

use App\DataExchange\Infrastructure\Persistence\Writer\BrandedPdfBuilderTrait;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingSessionPdfGenerator;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\HumanResources\Domain\Model\Employee\Employee;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfTrainingSessionPdfGenerator implements TrainingSessionPdfGenerator
{
    use BrandedPdfBuilderTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private string $uploadsDir,
    ) {}

    public function generate(TrainingSession $session, Employee $employee): string
    {
        $title = $this->translator->trans('Confirmación de asistencia a formación');

        $headers = [
            $this->translator->trans('Sesión'),
            $this->translator->trans('Fecha'),
            $this->translator->trans('Tema'),
            $this->translator->trans('Empleado'),
        ];

        $rows = [
            [
                $session->title(),
                $session->sessionDate()->format('d/m/Y'),
                $session->topic(),
                $employee->name(),
            ],
        ];

        $pdf = $this->createBrandedPDF($title);
        $pdf->AddPage();
        $this->renderBrandedTitle($pdf, $title, $session->sessionDate()->format('d / m / Y'));
        $this->setBrandedFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML($this->buildBrandedTableHtml($headers, $rows), true, false, true, false, '');
        $this->renderBrandedChromeOnEveryPage($pdf, mb_strtoupper($title));

        $path = sprintf('%s/training-sessions/%s.pdf', $this->uploadsDir, uniqid('ts-', true));

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->Output($path, 'F');

        return $path;
    }
}
