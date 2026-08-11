<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/carbon-footprint/property-trip/{token}/contribution/{contributionId}/certificate/email',
    name: 'public_carbon_footprint_property_trip_contribution_certificate_email',
    methods: ['POST'],
)]
class CertificateEmailController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;
    use PublicContributionCertificateTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetTripCalculationLinkByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        SendContributionCertificateService $sendContributionCertificate,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        if (!$this->isCsrfTokenValid('property_trip_' . $token, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $certificate = $this->resolveCertificate(
            $token,
            $contributionId,
            $getByToken,
            $projectContributionRepository,
            $contributionCertificateRepository,
        );

        if (null === $certificate) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $email = trim($request->request->getString('email'));

        try {
            $sendContributionCertificate(new SendContributionCertificateRequest(
                contributionCertificateId: $certificate->id(),
                email: '' === $email ? null : $email,
            ));

            $this->addFlash('success', new TranslatableMessage('Te hemos enviado el certificado por correo.'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('No hemos podido enviar el certificado a esa dirección.'));
        }

        return $this->redirectToRoute(
            'public_carbon_footprint_property_trip_contribution_return',
            ['token' => $token, 'contributionId' => $contributionId],
        );
    }
}
