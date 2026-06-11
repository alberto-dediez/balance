<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/history/{preventionPlanId}/{versionNumber}/download-signature-proof', name: 'prevention_plans_version_download_signature_proof', methods: ['GET'])]
class DownloadSignatureProofController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        int $versionNumber,
        PreventionPlanRepository $preventionPlanRepository,
        SignatureRequestRepository $signatureRequestRepository,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $preventionPlan);

        $version = $preventionPlan->versions()->findByVersionNumber($versionNumber);
        if (!$version instanceof PreventionPlanVersion) {
            throw $this->createNotFoundException('Versión no encontrada');
        }

        $signatureRequestId = $version->signatureRequestId();
        if (null === $signatureRequestId) {
            throw $this->createNotFoundException('Esta versión no tiene firma asociada.');
        }

        $signatureRequest = $signatureRequestRepository->find($signatureRequestId);
        if (null === $signatureRequest) {
            throw $this->createNotFoundException('Solicitud de firma no encontrada.');
        }

        $documentSigned = $signatureRequest->documentSigned();
        if (null === $documentSigned || null === $documentSigned->path() || !is_readable($documentSigned->path())) {
            throw $this->createNotFoundException('La prueba de firma no está disponible todavía.');
        }

        $response = new BinaryFileResponse($documentSigned->path());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'prueba-firma-version-' . $versionNumber . '.pdf',
        );

        return $response;
    }
}
