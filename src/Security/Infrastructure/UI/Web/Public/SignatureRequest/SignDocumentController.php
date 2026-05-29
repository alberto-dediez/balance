<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Public\SignatureRequest;

use App\Security\Application\SignatureRequest\Sign\SignDocumentRequest;
use App\Security\Application\SignatureRequest\Sign\SignDocumentService;
use App\Security\Domain\Model\SignatureRequest\SignatureAlreadySignedException;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sign/{token}', name: 'public_sign_document', methods: ['POST'])]
class SignDocumentController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $signatureTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        SignDocumentService $signDocument,
        Request $request,
    ): Response {
        $limiter = $this->signatureTokenLimiter->create($request->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $sessionKey = 'signature_otp_verified_' . hash('sha256', $token);
        if (!$request->getSession()->get($sessionKey)) {
            throw $this->createAccessDeniedException('Verificación OTP requerida.');
        }

        if (!$this->isCsrfTokenValid('sign_document_' . $token, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        try {
            $response = $signDocument(new SignDocumentRequest(
                tokenPlainValue: $token,
                acceptanceText: $request->request->getString('acceptance_text'),
                signerIp: $request->getClientIp() ?? '',
                signerUserAgent: $request->headers->get('User-Agent') ?? '',
                signerAcceptLanguage: $request->headers->get('Accept-Language') ?? '',
            ));
        } catch (SignatureTokenInvalidException) {
            return $this->render('@security/public/sign/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
        } catch (SignatureAlreadySignedException) {
            return $this->redirectToRoute('public_sign_view', ['token' => $token]);
        }

        return $this->render('@security/public/sign/success.html.twig', [
            'signatureRequest' => $response->signatureRequest,
        ]);
    }
}
