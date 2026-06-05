<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Public\SignatureRequest;

use App\Security\Application\SignatureRequest\SendOtp\SendOtpRequest;
use App\Security\Application\SignatureRequest\SendOtp\SendOtpService;
use App\Security\Application\SignatureRequest\VerifyOtp\VerifyOtpRequest;
use App\Security\Application\SignatureRequest\VerifyOtp\VerifyOtpService;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class OtpController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $signatureTokenLimiter,
    ) {}

    #[Route('/sign/{token}/otp', name: 'public_sign_otp', methods: ['GET'])]
    public function show(
        string $token,
        SendOtpService $sendOtp,
        Request $request,
    ): Response {
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
            return $this->redirectToRoute('public_sign_view', ['token' => $token]);
        }

        return $this->render('@security/public/sign/otp.html.twig', [
            'token' => $token,
            'notificationChannel' => $otpResponse->notificationChannel->value,
            'maskedDestination' => $otpResponse->maskedDestination,
            'attemptsLeft' => null,
            'error' => null,
        ]);
    }

    #[Route('/sign/{token}/otp', name: 'public_sign_otp_post', methods: ['POST'])]
    public function handle(
        string $token,
        SendOtpService $sendOtp,
        VerifyOtpService $verifyOtp,
        Request $request,
    ): Response {
        $limiter = $this->signatureTokenLimiter->create($request->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $action = $request->request->getString('action');

        if ('resend' === $action) {
            try {
                $otpResponse = $sendOtp(new SendOtpRequest(
                    tokenPlainValue: $token,
                    signerIp: $request->getClientIp() ?? '',
                    signerUserAgent: $request->headers->get('User-Agent') ?? '',
                ));
            } catch (SignatureTokenInvalidException) {
                return $this->render('@security/public/sign/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
            }

            return $this->render('@security/public/sign/otp.html.twig', [
                'token' => $token,
                'notificationChannel' => $otpResponse->notificationChannel->value,
                'maskedDestination' => $otpResponse->maskedDestination,
                'attemptsLeft' => null,
                'error' => null,
                'resent' => true,
            ]);
        }

        try {
            $verifyResponse = $verifyOtp(new VerifyOtpRequest(
                tokenPlainValue: $token,
                otpCode: $request->request->getString('otp_code'),
                signerIp: $request->getClientIp() ?? '',
            ));
        } catch (SignatureTokenInvalidException) {
            return $this->render('@security/public/sign/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
        }

        if ($verifyResponse->verified) {
            $request->getSession()->set('signature_otp_verified_' . hash('sha256', $token), true);

            return $this->redirectToRoute('public_sign_view', ['token' => $token]);
        }

        return $this->render('@security/public/sign/otp.html.twig', [
            'token' => $token,
            'notificationChannel' => $verifyResponse->notificationChannel->value,
            'maskedDestination' => $verifyResponse->maskedDestination,
            'attemptsLeft' => $verifyResponse->attemptsLeft,
            'error' => $verifyResponse->locked ? 'locked' : 'invalid',
        ]);
    }
}
