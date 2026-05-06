<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/download/{preventionPlanId}', name: 'prevention_plans_download_pdf')]
class DownloadPdfController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $repository,
        LoggedUser $loggedUser,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $preventionPlan);

        $pdfPath = $preventionPlan->getPDF();

        if (null === $pdfPath) {
            throw $this->createNotFoundException('No hay PDF disponible para este plan en el idioma actual.');
        }

        $absolutePath = $this->getParameter('app.uploads_dir') . '/' . $pdfPath;

        if (!file_exists($absolutePath)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'plan-prevencion-' . $preventionPlanId . '.pdf',
        );

        return $response;
    }
}
