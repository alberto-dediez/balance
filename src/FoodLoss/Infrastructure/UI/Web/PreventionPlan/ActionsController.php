<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\UpdateActions\ProposedActionData;
use App\FoodLoss\Application\PreventionPlan\UpdateActions\UpdatePreventionPlanActionsRequest;
use App\FoodLoss\Application\PreventionPlan\UpdateActions\UpdatePreventionPlanActionsService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdatePreventionPlanActionsType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/edit/{preventionPlanId}/actions', name: 'prevention_plans_actions')]
class ActionsController extends AbstractController
{
    use EditTrait;
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        UpdatePreventionPlanActionsService $updatePreventionPlanActions,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $version = $preventionPlan->currentPlanVersion();

        if (!$version->isValidated()) {
            return $this->redirectToRoute('prevention_plans_edit', ['preventionPlanId' => $preventionPlanId]);
        }

        if (!$version->isLoaded()) {
            return $this->render('@food_loss/prevention-plan/actions-loading.html.twig', [
                'preventionPlan' => $preventionPlan,
                'statusUrl' => $this->generateUrl('prevention_plans_version_status', ['preventionPlanId' => $preventionPlanId]),
            ]);
        }

        $currentProposedActions = [];
        foreach ($version->proposedActions()->toArray() as $proposedAction) {
            $currentProposedActions[] = new ProposedActionData(
                propertyId: $proposedAction->propertyId(),
                actionId: $proposedAction->actionId(),
                criticalPointId: $proposedAction->criticalPointId(),
            );
        }

        $updateRequest = new UpdatePreventionPlanActionsRequest(
            preventionPlanId: $preventionPlan->id(),
            proposedActions: $currentProposedActions,
        );

        $form = $this->createForm(UpdatePreventionPlanActionsType::class, $updateRequest, [
            'allowed_property_ids' => $preventionPlan->propertyIds(),
        ]);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePreventionPlanActions, $user): void {
                /** @var UpdatePreventionPlanActionsRequest $updateRequest */
                $updateRequest = $form->getData();

                $updatePreventionPlanActions($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Acciones del plan actualizadas con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('prevention_plans');
            }

            if ($form->isSubmitted() && $form->isValid()) {
                return $this->redirectToRoute('prevention_plans_draft', ['preventionPlanId' => $preventionPlanId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/prevention-plan/actions.html.twig', [
            'preventionPlan' => $preventionPlan,
            'form' => $form->createView(),
        ]);
    }
}
