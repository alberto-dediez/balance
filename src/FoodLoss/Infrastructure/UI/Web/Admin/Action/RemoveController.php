<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use App\FoodLoss\Application\Action\Remove\RemoveActionRequest;
use App\FoodLoss\Application\Action\Remove\RemoveActionService;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/actions/remove/{actionId}', name: 'admin_actions_remove')]
class RemoveController extends AbstractController
{
    use GetActionFromIdTrait;

    public function __invoke(
        string $actionId,
        ActionRepository $actionRepository,
        RemoveActionService $removeAction,
        LoggedUser $loggedUser,
    ): Response {
        $action = $this->getActionFromId($actionId, $actionRepository);

        try {
            $removeAction(new RemoveActionRequest($action->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la acción {action}', ['{action}' => $action->description()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la acción {action}', ['{action}' => $action->description()]));
        }

        return $this->redirectToRoute('admin_actions');
    }
}
