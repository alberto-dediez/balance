<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\Complete\CompletePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Complete\CompletePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/edit/{preventionPlanId}/publish', name: 'prevention_plans_publish')]
class PublishController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        CompletePreventionPlanService $completePreventionPlan,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $version = $preventionPlan->currentPlanVersion();

        if (!$version->isDraftApproved()) {
            return $this->redirectToRoute('prevention_plans_edit', ['preventionPlanId' => $preventionPlanId]);
        }

        $form = $this->createFormBuilder()
            ->add('signed', CheckboxType::class, [
                'label' => new TranslatableMessage('¿Estas seguro de que quieres publicar una nueva versión de este plan de prevención?'),
                'required' => true,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('publish', SubmitType::class, [
                'label' => new TranslatableMessage('Publicar'),
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            assert($form instanceof Form);

            $clickedButton = $form->getClickedButton();
            if ($clickedButton instanceof FormInterface && 'saveAndReturn' === $clickedButton->getName()) {
                return $this->redirectToRoute('prevention_plans');
            }

            try {
                $completePreventionPlan(new CompletePreventionPlanRequest($preventionPlan->id()), $user);

                $this->addFlash('success', new TranslatableMessage('Plan publicado con éxito'));

                return $this->redirectToRoute('prevention_plans');
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }
        }

        return $this->render('@food_loss/prevention-plan/publish.html.twig', [
            'preventionPlan' => $preventionPlan,
            'form' => $form->createView(),
        ]);
    }
}
