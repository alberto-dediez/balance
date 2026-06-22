<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/training-sessions/{trainingSessionId}/attendances/{trainingAttendanceId}/download', name: 'training_attendances_download')]
class DownloadController extends AbstractController
{
    use GetTrainingAttendanceFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        string $trainingAttendanceId,
        TrainingAttendanceRepository $trainingAttendanceRepository,
        LoggedUser $loggedUser,
    ): Response {
        $attendance = $this->getTrainingAttendanceFromId($trainingAttendanceId, $trainingAttendanceRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $attendance);

        $filePath = $attendance->filePath();
        $path = str_starts_with($filePath, '/')
            ? $filePath
            : $this->getParameter('app.uploads_dir') . '/' . $filePath;

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'hoja-asistencia-' . $trainingAttendanceId . '.pdf',
        );

        return $response;
    }
}
