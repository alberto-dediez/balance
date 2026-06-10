<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Public\SignatureRequest;

use App\Security\Application\SignatureRequest\Verify\VerifySignatureRequest;
use App\Security\Application\SignatureRequest\Verify\VerifySignatureService;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sign/verify/{signatureRequestId}', name: 'public_sign_verify', methods: ['GET'])]
class VerifyController extends AbstractController
{
    public function __invoke(
        string $signatureRequestId,
        VerifySignatureService $verifySignature,
    ): Response {
        try {
            $response = $verifySignature(new VerifySignatureRequest(
                new SignatureRequestId($signatureRequestId),
            ));
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Solicitud de firma no encontrada.');
        }

        return $this->render('@security/public/sign/verify.html.twig', [
            'signatureRequest' => $response->signatureRequest,
            'verificationResult' => $response->verificationResult,
        ]);
    }
}
