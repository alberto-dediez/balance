<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCertificateTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/contribution/{contributionId}/certificate',
    name: 'public_carbon_footprint_property_trip_contribution_certificate',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/contribution/{contributionId}/certificate',
    name: 'public_carbon_footprint_trip_contribution_certificate',
    methods: ['GET'],
)]
class CertificateController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;
    use ContributionCertificateTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetTripCalculationLinkByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        TripCalculationRepository $tripCalculationRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        DownloadContributionCertificateService $downloadContributionCertificate,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);
        $accountId = null !== $link ? $this->accountIdOf($link) : null;

        if (
            null === $link
            || !$accountId instanceof AccountId
            || null === $this->resolveContribution($contributionId, $link, $projectContributionRepository, $tripCalculationRepository)
        ) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $certificate = $this->resolveCertificate(
            $accountId,
            $contributionId,
            $projectContributionRepository,
            $contributionCertificateRepository,
        );

        if (null === $certificate) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        try {
            $response = $downloadContributionCertificate(
                new DownloadContributionCertificateRequest($certificate->id()),
            );
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->streamFile($response);
    }

    private function streamFile(DownloadContributionCertificateResponse $response): StreamedResponse
    {
        $stream = $response->stream;

        $streamedResponse = new StreamedResponse(static function () use ($stream): void {
            $output = fopen('php://output', 'wb');

            if (false !== $output) {
                stream_copy_to_stream($stream, $output);
                fclose($output);
            }

            fclose($stream);
        });

        $streamedResponse->headers->set('Content-Type', $response->mimeType);
        $streamedResponse->headers->set(
            'Content-Disposition',
            ContentDisposition::attachment($response->filename),
        );

        return $streamedResponse;
    }
}
