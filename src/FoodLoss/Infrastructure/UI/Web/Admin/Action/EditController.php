<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use App\FoodLoss\Application\Action\Update\UpdateActionRequest;
use App\FoodLoss\Application\Action\Update\UpdateActionService;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateActionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/actions/edit/{actionId}', name: 'admin_actions_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetActionFromIdTrait;

    public function __invoke(
        string $actionId,
        ActionRepository $actionRepository,
        UpdateActionService $updateAction,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $action = $this->getActionFromId($actionId, $actionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $action);

        $actionRequest = new UpdateActionRequest(
            actionId: $action->id(),
            description: $action->description(),
            priority: $action->priority(),
        );

        try {
            $form = $this->createForm(UpdateActionType::class, $actionRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAction, $loggedUser): void {
                $actionRequest = $form->getData();

                $updateAction($actionRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Acción actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_actions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/actions/edit.html.twig', [
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }
}
