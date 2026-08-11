<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Contribution;

use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/attendee-survey/{token}/contribution/{contributionId}/return',
    name: 'public_carbon_footprint_attendee_survey_contribution_return',
    methods: ['GET'],
)]
class ReturnController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use AttendeeSurveyContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetAttendeeSurveyByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ProjectRepository $projectRepository,
        PaymentRepository $paymentRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        ConfirmProjectContributionService $confirmProjectContribution,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        $survey = $this->resolveSurvey($token, $getByToken);

        if (null === $survey) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($survey);
        $contributionAccountId = $projectContribution?->accountId();
        $originId = $projectContribution?->originId();

        if (
            null === $projectContribution
            || null === $accountId
            || null === $contributionAccountId
            || !$accountId->equals($contributionAccountId)
            || null === $originId
        ) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $paymentId = $projectContribution->paymentId();
        $payment = $paymentId instanceof PaymentId ? $paymentRepository->find($paymentId) : null;

        // The webhook may not have landed yet, so the gateway state decides rather than the redirect itself.
        if ($projectContribution->isPending() && null !== $payment && $payment->isPaid()) {
            $confirmProjectContribution(new ConfirmProjectContributionRequest($projectContribution->id()));
        }

        $projectId = $projectContribution->projectId();

        return $this->render('@carbon_footprint/public/project_contribution/confirmation.html.twig', [
            'token' => $token,
            'event' => $survey->event,
            'routes' => $this->contributionRoutes($token, $originId),
            'contribution' => $projectContribution,
            'project' => null !== $projectId ? $projectRepository->find($projectId) : null,
            'payment' => $payment,
            'certificate' => $contributionCertificateRepository->findByProjectContributionId($projectContribution->id()),
        ]);
    }
}
