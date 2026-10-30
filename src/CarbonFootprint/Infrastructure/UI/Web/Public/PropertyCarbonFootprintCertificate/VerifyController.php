<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify\VerifyPropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify\VerifyPropertyCarbonFootprintCertificateService;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/footprint-certificate/{token}',
    name: 'public_property_carbon_footprint_certificate_verify',
    requirements: ['token' => '[A-Za-z0-9_-]{22,64}'],
    methods: ['GET'],
)]
class VerifyController extends AbstractController
{
    use BrandedPublicControllerTrait;

    public function __construct(private RateLimiterFactory $certificateVerificationLimiter) {}

    public function __invoke(
        string $token,
        VerifyPropertyCarbonFootprintCertificateService $verifyCertificate,
        Request $request,
    ): Response {
        if (!$this->certificateVerificationLimiter->create($request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $certificate = $verifyCertificate(new VerifyPropertyCarbonFootprintCertificateRequest($token));

        if (null === $certificate) {
            throw $this->createNotFoundException();
        }

        $this->brandFor($certificate->accountId);

        $response = $this->render('@carbon_footprint/public/footprint_certificate/verify.html.twig', [
            'certificate' => $certificate,
        ]);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
