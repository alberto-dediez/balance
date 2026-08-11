<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution\PublicContributionCertificateTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/contribution/{contributionId}/certificate/email',
    name: 'public_carbon_footprint_attendee_survey_contribution_certificate_email',
    methods: ['POST'],
)]
class CertificateEmailController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;
    use PublicContributionCertificateTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
        private RateLimiterFactory $certificateResendLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetAttendeeSurveyByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        SendContributionCertificateService $sendContributionCertificate,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        if (!$this->isCsrfTokenValid('attendee_survey_contribution_' . $token, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
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

        $this->resendCertificate(
            $certificate,
            trim($request->request->getString('email')),
            $this->certificateResendLimiter,
            $sendContributionCertificate,
        );

        return $this->redirectToRoute(
            'public_carbon_footprint_attendee_survey_contribution_return',
            ['token' => $token, 'contributionId' => $contributionId],
        );
    }
}
