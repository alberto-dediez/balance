<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/edit/{preventionPlanId}/publish', name: 'prevention_plans_publish')]
class PublishController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $version = $preventionPlan->currentPlanVersion();

        if (!$version->isDraftApproved()) {
            return $this->redirectToRoute('prevention_plans_edit', ['preventionPlanId' => $preventionPlanId]);
        }

        return $this->render('@food_loss/prevention-plan/publish.html.twig', [
            'preventionPlan' => $preventionPlan,
            'signatureRequestId' => $version->signatureRequestId(),
        ]);
    }
}
