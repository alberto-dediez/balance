<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Download\DownloadContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCertificateTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\UI\Web\Download\ContentDisposition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/contribution/{contributionId}/certificate',
    name: 'public_carbon_footprint_attendee_survey_contribution_certificate',
    methods: ['GET'],
)]
class CertificateController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;
    use ContributionCertificateTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetAttendeeSurveyByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        DownloadContributionCertificateService $downloadContributionCertificate,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        $survey = $this->resolveSurvey($token, $getByToken);
        $accountId = null !== $survey ? $this->accountIdOf($survey) : null;

        if (!$accountId instanceof AccountId) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $certificate = $this->resolveCertificate(
            $accountId,
            $contributionId,
            $projectContributionRepository,
            $contributionCertificateRepository,
        );

        if (null === $certificate) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
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
