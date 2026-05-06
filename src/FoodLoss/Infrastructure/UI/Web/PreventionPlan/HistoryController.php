<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/history/{preventionPlanId}', name: 'prevention_plans_history')]
class HistoryController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $preventionPlan);

        $versions = $preventionPlan->versions()->toArray();
        usort($versions, static fn(PreventionPlanVersion $a, PreventionPlanVersion $b) => $b->versionNumber() - $a->versionNumber());

        return $this->render('@food_loss/prevention-plan/history.html.twig', [
            'preventionPlan' => $preventionPlan,
            'versions' => $versions,
        ]);
    }
}
