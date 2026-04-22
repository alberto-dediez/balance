<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\UpdateSections\SectionData;
use App\FoodLoss\Application\PreventionPlan\UpdateSections\UpdatePreventionPlanSectionsRequest;
use App\FoodLoss\Application\PreventionPlan\UpdateSections\UpdatePreventionPlanSectionsService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdatePreventionPlanSectionsType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/edit/{preventionPlanId}/draft', name: 'prevention_plans_draft')]
class DraftController extends AbstractController
{
    use EditTrait;
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        UpdatePreventionPlanSectionsService $updatePreventionPlanSections,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $version = $preventionPlan->currentPlanVersion();

        if ($version->isGenerating()) {
            return $this->render('@food_loss/prevention-plan/draft-loading.html.twig', [
                'preventionPlan' => $preventionPlan,
            ]);
        }

        $currentSections = [];
        foreach ($version->sections()->toArray() as $section) {
            $currentSections[] = new SectionData(
                sectionId: $section->id(),
                title: $section->title(),
                content: $section->content(),
            );
        }

        $updateRequest = new UpdatePreventionPlanSectionsRequest(
            preventionPlanId: $preventionPlan->id(),
            sections: $currentSections,
        );

        $form = $this->createForm(UpdatePreventionPlanSectionsType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePreventionPlanSections, $user): void {
                /** @var UpdatePreventionPlanSectionsRequest $updateRequest */
                $updateRequest = $form->getData();

                $updatePreventionPlanSections($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Borrador del plan actualizado con éxito'));
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

        return $this->render('@food_loss/prevention-plan/draft.html.twig', [
            'preventionPlan' => $preventionPlan,
            'form' => $form->createView(),
        ]);
    }
}
