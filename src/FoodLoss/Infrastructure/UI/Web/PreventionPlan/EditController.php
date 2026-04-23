<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\Update\UpdatePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Update\UpdatePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdatePreventionPlanType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/edit/{preventionPlanId}', name: 'prevention_plans_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        UpdatePreventionPlanService $updatePreventionPlan,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $updateRequest = new UpdatePreventionPlanRequest(
            preventionPlanId: $preventionPlan->id(),
            name: $preventionPlan->name(),
            properties: $preventionPlan->propertyIds(),
        );

        $form = $this->createForm(UpdatePreventionPlanType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePreventionPlan, $user): void {
                /** @var UpdatePreventionPlanRequest $updateRequest */
                $updateRequest = $form->getData();

                $updatePreventionPlan($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Plan de prevención actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('prevention_plans');
            }

            if ($form->isSubmitted() && $form->isValid()) {
                return $this->redirectToRoute('prevention_plans_validate', ['preventionPlanId' => $preventionPlanId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/prevention-plan/edit.html.twig', [
            'preventionPlan' => $preventionPlan,
            'form' => $form->createView(),
        ]);
    }
}
