<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Public\SignatureRequest;

use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewRequest;
use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewService;
use App\Security\Application\SignatureRequest\SendOtp\SendOtpRequest;
use App\Security\Application\SignatureRequest\SendOtp\SendOtpService;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sign/{token}', name: 'public_sign_view', methods: ['GET'])]
class ViewSignatureController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $signatureTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        RecordSignatureViewService $recordView,
        SendOtpService $sendOtp,
        Request $request,
    ): Response {
        $limiter = $this->signatureTokenLimiter->create($request->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $sessionKey = 'signature_otp_verified_' . hash('sha256', $token);
        $session = $request->getSession();

        if (!$session->get($sessionKey)) {
            try {
                $otpResponse = $sendOtp(new SendOtpRequest(
                    tokenPlainValue: $token,
                    signerIp: $request->getClientIp() ?? '',
                    signerUserAgent: $request->headers->get('User-Agent') ?? '',
                ));
            } catch (SignatureTokenInvalidException) {
                return $this->render('@security/public/sign/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
            }

            if ($otpResponse->alreadyVerified) {
                $session->set($sessionKey, true);
            } else {
                return $this->redirectToRoute('public_sign_otp', ['token' => $token]);
            }
        }

        try {
            $response = $recordView(new RecordSignatureViewRequest(
                tokenPlainValue: $token,
                signerIp: $request->getClientIp() ?? '',
                signerUserAgent: $request->headers->get('User-Agent') ?? '',
            ));
        } catch (SignatureTokenInvalidException) {
            return $this->render('@security/public/sign/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
        }

        if ($response->alreadySigned) {
            return $this->render('@security/public/sign/already-signed.html.twig', [
                'signatureRequest' => $response->signatureRequest,
            ]);
        }

        $httpResponse = new Response();
        $httpResponse->headers->set('Referrer-Policy', 'no-referrer');

        return $this->render('@security/public/sign/view.html.twig', [
            'signatureRequest' => $response->signatureRequest,
            'token' => $token,
        ], $httpResponse);
    }
}
