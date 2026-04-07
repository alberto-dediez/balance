<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\Remove\RemovePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Remove\RemovePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/remove/{preventionPlanId}', name: 'prevention_plans_remove')]
class RemoveController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        RemovePreventionPlanService $removePreventionPlan,
        LoggedUser $loggedUser,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(Action::Remove->value, $preventionPlan);

        try {
            $removePreventionPlan(new RemovePreventionPlanRequest($preventionPlan->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el plan de prevención {name}', ['{name}' => $preventionPlan->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el plan de prevención {name}', ['{name}' => $preventionPlan->name()]));
        }

        return $this->redirectToRoute('prevention_plans');
    }
}
