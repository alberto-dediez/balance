<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\Register\RegisterTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Register\RegisterTrainingSessionService;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterTrainingSessionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/register', name: 'training_sessions_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterTrainingSessionService $registerTrainingSession,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(
            SecurityAction::Register->value,
            new TrainingSession(id: TrainingSessionId::generate(), accountId: $account?->id()),
        );

        assert($account instanceof Account);

        $form = $this->createForm(RegisterTrainingSessionType::class, new RegisterTrainingSessionRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerTrainingSession, $user, $account): true {
                /** @var RegisterTrainingSessionRequest $registerRequest */
                $registerRequest = $form->getData();

                $registerRequest->accountId = $account->id();

                $response = $registerTrainingSession($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Sesión formativa creada correctamente: {title}', ['{title}' => $response->title]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('training_sessions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-session/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
