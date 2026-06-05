<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\Verify\VerifySignatureRequest;
use App\Security\Application\SignatureRequest\Verify\VerifySignatureService;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/signature-requests/{signatureRequestId}', name: 'admin_signature_requests_show', methods: ['GET'])]
class ShowController extends AbstractController
{
    use GetSignatureRequestFromIdTrait;

    public function __invoke(
        string $signatureRequestId,
        SignatureRequestRepository $repository,
        VerifySignatureService $verifySignature,
    ): Response {
        $signatureRequest = $this->getSignatureRequestFromId($signatureRequestId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $signatureRequest);

        $verificationResult = null;

        if (SignatureRequestStatus::Signed === $signatureRequest->status()) {
            try {
                $response = $verifySignature(new VerifySignatureRequest(new SignatureRequestId($signatureRequestId)));
                $verificationResult = $response->verificationResult;
            } catch (\InvalidArgumentException) {
                // pass — show page without verification
            }
        }

        return $this->render('@security/admin/signature-requests/show.html.twig', [
            'signatureRequest' => $signatureRequest,
            'verificationResult' => $verificationResult,
        ]);
    }
}
