<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/compensate/{origin}/{originId}/{contributionId}/cancel', name: 'owner_contribution_cancel', methods: ['GET'])]
class CancelController extends AbstractController
{
    use OwnerContributionTrait;

    public function __invoke(
        string $origin,
        string $originId,
        string $contributionId,
        OwnerContributionContextResolver $contextResolver,
        ProjectContributionRepository $projectContributionRepository,
        LoggedUser $loggedUser,
    ): Response {
        $context = $this->resolveContext($origin, $originId, $contextResolver, $loggedUser());

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $contributionAccountId = $projectContribution?->accountId();
        $projectId = $projectContribution?->projectId();

        if (null === $contributionAccountId || null === $projectId || !$context->accountId->equals($contributionAccountId)) {
            throw $this->createNotFoundException('Aportación no encontrada.');
        }

        $this->addFlash('warning', new TranslatableMessage('Has cancelado el pago. Puedes volver a intentarlo cuando quieras.'));

        return $this->redirectToRoute('owner_contribution_checkout', [
            'origin' => $origin,
            'originId' => $originId,
            'projectId' => (string) $projectId,
        ]);
    }
}
