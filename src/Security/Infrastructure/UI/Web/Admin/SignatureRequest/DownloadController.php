<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/signature-requests/{signatureRequestId}/download', name: 'admin_signature_requests_download', methods: ['GET'])]
class DownloadController extends AbstractController
{
    use GetSignatureRequestFromIdTrait;

    public function __invoke(
        string $signatureRequestId,
        SignatureRequestRepository $repository,
    ): Response {
        $signatureRequest = $this->getSignatureRequestFromId($signatureRequestId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $signatureRequest);

        $documentSigned = $signatureRequest->documentSigned();

        if (null === $documentSigned || null === $documentSigned->path() || !is_readable($documentSigned->path())) {
            throw $this->createNotFoundException('Documento firmado no disponible.');
        }

        $response = new BinaryFileResponse($documentSigned->path());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'signed_document.pdf');

        return $response;
    }
}
