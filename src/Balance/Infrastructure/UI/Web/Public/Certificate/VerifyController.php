<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Public\Certificate;

use App\Balance\Application\ContributionCertificate\Verify\VerifyContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Verify\VerifyContributionCertificateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/certificate/{token}',
    name: 'public_contribution_certificate_verify',
    requirements: ['token' => '[A-Za-z0-9_-]{22,64}'],
    methods: ['GET'],
)]
class VerifyController extends AbstractController
{
    public function __construct(private RateLimiterFactory $certificateVerificationLimiter) {}

    public function __invoke(
        string $token,
        VerifyContributionCertificateService $verifyContributionCertificate,
        Request $request,
    ): Response {
        if (!$this->certificateVerificationLimiter->create($request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $certificate = $verifyContributionCertificate(new VerifyContributionCertificateRequest($token));

        if (null === $certificate) {
            throw $this->createNotFoundException();
        }

        return $this->render('@balance/public/certificate/verify.html.twig', [
            'certificate' => $certificate,
        ]);
    }
}
