<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/contribution/{contributionId}/return',
    name: 'public_carbon_footprint_property_trip_contribution_return',
    methods: ['GET'],
)]
class ReturnController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetTripCalculationLinkByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        ProjectRepository $projectRepository,
        PaymentRepository $paymentRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        ConfirmProjectContributionService $confirmProjectContribution,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($link);
        $contributionAccountId = $projectContribution?->accountId();

        if (
            null === $projectContribution
            || null === $accountId
            || null === $contributionAccountId
            || !$accountId->equals($contributionAccountId)
        ) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $paymentId = $projectContribution->paymentId();
        $payment = $paymentId instanceof PaymentId ? $paymentRepository->find($paymentId) : null;

        if ($projectContribution->isPending() && null !== $payment && $payment->isPaid()) {
            $confirmProjectContribution(new ConfirmProjectContributionRequest($projectContribution->id()));
        }

        $projectId = $projectContribution->projectId();

        return $this->render('@carbon_footprint/public/project_contribution/confirmation.html.twig', [
            'token' => $token,
            'property' => $link->property,
            'routes' => $this->contributionRoutes($token, (string) $projectContribution->originId()),
            'contribution' => $projectContribution,
            'project' => null !== $projectId ? $projectRepository->find($projectId) : null,
            'payment' => $payment,
            'certificate' => $contributionCertificateRepository->findByProjectContributionId($projectContribution->id()),
        ]);
    }
}
