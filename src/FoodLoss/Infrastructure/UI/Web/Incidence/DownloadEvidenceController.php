<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/incidences/{incidenceId}/evidence/{evidenceId}/download', name: 'incidences_evidence_download')]
class DownloadEvidenceController extends AbstractController
{
    use GetIncidenceFromIdTrait;

    public function __invoke(
        string $incidenceId,
        string $evidenceId,
        IncidenceRepository $incidenceRepository,
        LoggedUser $loggedUser,
    ): Response {
        $incidence = $this->getIncidenceFromId($incidenceId, $incidenceRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $incidence);

        $evidence = null;
        foreach ($incidence->evidences()->toArray() as $candidate) {
            if ($candidate->id()->equals(new IncidenceEvidenceId($evidenceId))) {
                $evidence = $candidate;
                break;
            }
        }

        if (null === $evidence) {
            throw $this->createNotFoundException('Evidencia no encontrada.');
        }

        $path = $this->getParameter('app.uploads_dir') . '/' . $evidence->path();

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $evidence->originalName(),
        );

        return $response;
    }
}
