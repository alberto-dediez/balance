<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\Elimination\AssessEliminationRequest;
use App\FoodLoss\Application\Surplus\Assess\Elimination\AssessEliminationService;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusEliminationAssessmentType;
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

#[Route('/surpluses/edit/{surplusId}/elimination', name: 'surpluses_elimination')]
class EliminationAssessmentController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        AssessEliminationService $assessElimination,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $elimination = $surplus->eliminationAssessment();
        $currentCanBeEliminated = $elimination->isAnswered() ? $elimination->canBeEliminated() : null;

        $form = $this->createForm(SurplusEliminationAssessmentType::class, [
            'canBeEliminated' => $currentCanBeEliminated,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $canBeEliminated = null;

            try {
                $data = $form->getData();
                $canBeEliminated = $data['canBeEliminated'];

                $assessElimination(
                    new AssessEliminationRequest(
                        surplusId: $surplus->id(),
                        canBeEliminated: $canBeEliminated,
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Eliminación actualizada con éxito'));
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

            if (false === $canBeEliminated) {
                $this->addFlash('info', new TranslatableMessage('Reiniciamos el proceso de clasificacion para aprovechar el excedente'));

                return $this->redirectToRoute('surpluses_transformation', ['surplusId' => $surplusId]);
            }

            return $this->redirectToRoute('surpluses');
        }

        return $this->render('@food_loss/surpluses/elimination.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
