<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery-logs/{deliveryLogId}/download-signed-document', name: 'delivery_logs_download_signed_document', methods: ['GET'])]
class DownloadSignedDocumentController extends AbstractController
{
    use GetDeliveryLogFromIdTrait;

    public function __invoke(
        string $deliveryLogId,
        DeliveryLogRepository $deliveryLogRepository,
        SignatureRequestRepository $signatureRequestRepository,
    ): Response {
        $deliveryLog = $this->getDeliveryLogFromId($deliveryLogId, $deliveryLogRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $deliveryLog);

        $signatureRequestId = $deliveryLog->signatureRequestId();
        if (null === $signatureRequestId) {
            throw $this->createNotFoundException('Este registro de entrega no tiene firma asociada.');
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
            'prueba-firma-entrega-' . $deliveryLogId . '.pdf',
        );

        return $response;
    }
}
