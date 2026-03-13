<?php

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\FoodLoss\Application\Action\Remove\RemoveActionRequest;
use App\FoodLoss\Application\Action\Remove\RemoveActionService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/actions/remove/{actionId}', name: 'admin_actions_remove')]
class RemoveController extends AbstractController
{
    use GetActionFromIdTrait;

    public function __invoke(
        string $actionId, 
        ActionRepository $actionRepository,
        RemoveActionService $removeAction,
        LoggedUser $loggedUser
    ): Response
    {   
        $action = $this->getActionFromId($actionId, $actionRepository);

        try {
            $removeAction(new RemoveActionRequest($action->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la acción {action}", ['{action}' => $action->description()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la acción {action}", ['{action}' => $action->description()]));
        }

        return $this->redirectToRoute('admin_actions');
    }
}
