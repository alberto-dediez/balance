<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Public\SignatureRequest;

use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewRequest;
use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewService;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sign/{token}/document', name: 'public_sign_stream_document', methods: ['GET'])]
class StreamDocumentController extends AbstractController
{
    public function __invoke(
        string $token,
        RecordSignatureViewService $recordView,
        Request $request,
    ): Response {
        try {
            $response = $recordView(new RecordSignatureViewRequest(
                tokenPlainValue: $token,
                signerIp: $request->getClientIp() ?? '',
                signerUserAgent: $request->headers->get('User-Agent') ?? '',
            ));
        } catch (SignatureTokenInvalidException) {
            throw $this->createNotFoundException();
        }

        if ($response->alreadySigned) {
            throw $this->createNotFoundException();
        }

        $path = $response->signatureRequest->documentToSign()->path();

        if (null === $path || !is_readable($path)) {
            throw $this->createNotFoundException('Documento no disponible.');
        }

        $binaryResponse = new BinaryFileResponse($path);
        $binaryResponse->headers->set('Content-Type', 'application/pdf');
        $binaryResponse->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $binaryResponse->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $binaryResponse->headers->set('Pragma', 'no-cache');
        $binaryResponse->headers->set('Referrer-Policy', 'no-referrer');

        return $binaryResponse;
    }
}
