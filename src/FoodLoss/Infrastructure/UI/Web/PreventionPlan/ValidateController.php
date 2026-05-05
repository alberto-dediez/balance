<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan\ValidatePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan\ValidatePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/edit/{preventionPlanId}/validate', name: 'prevention_plans_validate')]
class ValidateController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        ValidatePreventionPlanService $validatePreventionPlan,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $response = $validatePreventionPlan(new ValidatePreventionPlanRequest($preventionPlan->id()));

        if (!$response->hasErrors) {
            return $this->redirectToRoute('prevention_plans_actions', ['preventionPlanId' => $preventionPlanId]);
        }

        return $this->render('@food_loss/prevention-plan/validate.html.twig', [
            'preventionPlan' => $preventionPlan,
            'planErrors' => $response->planErrors,
            'invalidProperties' => $response->invalidProperties,
        ]);
    }
}
