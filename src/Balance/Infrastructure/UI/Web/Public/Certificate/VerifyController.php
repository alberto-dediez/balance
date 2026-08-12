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

/**
 * Destination of the QR printed on every certificate. It is only reachable with the token the QR
 * carries, and it answers with the reference to match against the paper and what the document
 * proves — never with anything about who paid for it. A social contribution reaches here too, and
 * the page names it for what it is: nothing on it is claimed to be retired or offset.
 */
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
        // Tokens are unguessable one by one, but the limiter keeps anybody from sweeping for them.
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
