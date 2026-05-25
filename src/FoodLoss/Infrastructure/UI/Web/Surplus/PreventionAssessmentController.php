<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusService;
use App\FoodLoss\Domain\Model\Surplus\PreventionAssessment;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusPreventionAssessmentType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/surpluses/edit/{surplusId}/prevention', name: 'surpluses_prevention')]
class PreventionAssessmentController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        UpdateSurplusService $updateSurplus,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $prevention = $surplus->preventionAssessment();
        $currentCanBePrevented = $prevention->isAnswered() ? $prevention->canBePrevented() : null;

        $form = $this->createForm(SurplusPreventionAssessmentType::class, [
            'canBePrevented' => $currentCanBePrevented,
            'actionId' => $prevention->actionId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();

                $updateRequest = new UpdateSurplusRequest(
                    surplusId: $surplus->id(),
                    date: $surplus->date(),
                    serviceCategoryId: $surplus->serviceCategoryId(),
                    criticalPointId: $surplus->criticalPointId(),
                    surplusCauseId: $surplus->surplusCauseId(),
                    foodType: $surplus->foodType(),
                    quantity: $surplus->quantity(),
                    unit: $surplus->unit(),
                    preventionAssessment: new PreventionAssessment(
                        canBePrevented: $data['canBePrevented'],
                        actionId: $data['actionId'],
                    ),
                    transformationAssessment: $surplus->transformationAssessment(),
                    humanDonationAssessment: $surplus->humanDonationAssessment(),
                    animalFeedAssessment: $surplus->animalFeedAssessment(),
                    compostingAssessment: $surplus->compostingAssessment(),
                    eliminationAssessment: $surplus->eliminationAssessment(),
                );

                $updateSurplus($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Prevención actualizada con éxito'));
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar correctamente.'));
            }

            assert($form instanceof Form);
            $clickedButton = $form->getClickedButton();

            if ($clickedButton instanceof FormInterface && 'saveAndReturn' === $clickedButton->getName()) {
                return $this->redirectToRoute('surpluses');
            }

            return $this->redirectToRoute('surpluses_transformation', ['surplusId' => $surplusId]);
        }

        return $this->render('@food_loss/surpluses/prevention.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
