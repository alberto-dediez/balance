<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\UI\Web\ProjectContribution\GetProjectContributionFromIdTrait;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Security\Application\Action;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contributions/{projectContributionId}', name: 'admin_project_contributions_view')]
class ViewController extends AbstractController
{
    use GetProjectContributionFromIdTrait;

    public function __invoke(
        string $projectContributionId,
        ProjectContributionRepository $projectContributionRepository,
        ProjectRepository $projectRepository,
        PaymentRepository $paymentRepository,
    ): Response {
        $projectContribution = $this->getProjectContributionFromId($projectContributionId, $projectContributionRepository);

        $this->denyAccessUnlessGranted(Action::Admin->value, $projectContribution);

        $projectId = $projectContribution->projectId();
        $paymentId = $projectContribution->paymentId();

        return $this->render('@balance/admin/contributions/view.html.twig', [
            'contribution' => $projectContribution,
            'project' => null !== $projectId ? $projectRepository->find($projectId) : null,
            'payment' => $paymentId instanceof PaymentId ? $paymentRepository->find($paymentId) : null,
        ]);
    }
}
