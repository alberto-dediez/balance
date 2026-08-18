<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compensate/{origin}/{originId}/{contributionId}/done', name: 'owner_contribution_confirmation', methods: ['GET'])]
class ConfirmationController extends AbstractController
{
    use OwnerContributionTrait;

    public function __invoke(
        string $origin,
        string $originId,
        string $contributionId,
        OwnerContributionContextResolver $contextResolver,
        ProjectContributionRepository $projectContributionRepository,
        ProjectRepository $projectRepository,
        PaymentRepository $paymentRepository,
        ContributionCertificateRepository $contributionCertificateRepository,
        ConfirmProjectContributionService $confirmProjectContribution,
        LoggedUser $loggedUser,
    ): Response {
        $context = $this->resolveContext($origin, $originId, $contextResolver, $loggedUser());
        $projectContribution = $this->resolveContribution($context, $contributionId, $projectContributionRepository);

        $paymentId = $projectContribution->paymentId();
        $payment = $paymentId instanceof PaymentId ? $paymentRepository->find($paymentId) : null;

        if ($projectContribution->isPending() && null !== $payment && $payment->isPaid()) {
            $confirmProjectContribution(new ConfirmProjectContributionRequest($projectContribution->id()));
        }

        $projectId = $projectContribution->projectId();

        return $this->render('@carbon_footprint/owner_contribution/confirmation.html.twig', [
            'originName' => $context->originName,
            'routes' => $this->contributionRoutes($context),
            'contribution' => $projectContribution,
            'project' => null !== $projectId ? $projectRepository->find($projectId) : null,
            'payment' => $payment,
            'certificate' => $contributionCertificateRepository->findByProjectContributionId($projectContribution->id()),
        ]);
    }

    private function resolveContribution(
        OwnerContributionContext $context,
        string $contributionId,
        ProjectContributionRepository $projectContributionRepository,
    ): ProjectContribution {
        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $contributionAccountId = $projectContribution?->accountId();

        if (null === $projectContribution || null === $contributionAccountId || !$context->accountId->equals($contributionAccountId)) {
            throw $this->createNotFoundException('Aportación no encontrada.');
        }

        return $projectContribution;
    }
}
