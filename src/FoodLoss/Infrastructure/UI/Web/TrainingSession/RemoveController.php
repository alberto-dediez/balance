<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\Remove\RemoveTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Remove\RemoveTrainingSessionService;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/remove/{trainingSessionId}', name: 'training_sessions_remove')]
class RemoveController extends AbstractController
{
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        RemoveTrainingSessionService $removeTrainingSession,
        LoggedUser $loggedUser,
    ): Response {
        $trainingSession = $this->getTrainingSessionFromId($trainingSessionId, $trainingSessionRepository);

        try {
            $removeTrainingSession(new RemoveTrainingSessionRequest($trainingSession->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la sesión formativa {title}', ['{title}' => $trainingSession->title()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la sesión formativa {title}', ['{title}' => $trainingSession->title()]));
        }

        return $this->redirectToRoute('training_sessions');
    }
}
