<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\Update\UpdateTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Update\UpdateTrainingSessionService;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateTrainingSessionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/edit/{trainingSessionId}', name: 'training_sessions_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        UpdateTrainingSessionService $updateTrainingSession,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $trainingSession = $this->getTrainingSessionFromId($trainingSessionId, $trainingSessionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $trainingSession);

        $updateRequest = new UpdateTrainingSessionRequest(
            trainingSessionId: $trainingSession->id(),
            sessionDate: $trainingSession->sessionDate(),
            title: $trainingSession->title(),
            topic: $trainingSession->topic(),
        );

        $form = $this->createForm(UpdateTrainingSessionType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateTrainingSession, $user): void {
                /** @var UpdateTrainingSessionRequest $updateRequest */
                $updateRequest = $form->getData();

                $updateTrainingSession($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Sesión formativa actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('training_sessions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-session/edit.html.twig', [
            'trainingSession' => $trainingSession,
            'form' => $form->createView(),
        ]);
    }
}
