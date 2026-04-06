<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use App\FoodLoss\Application\Action\Register\RegisterActionService;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterActionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/actions/register', name: 'admin_actions_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterActionService $registerAction, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Action(ActionId::generate()));

        $form = $this->createForm(RegisterActionType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAction, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerAction($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Acción creada correctamente: {description}', ['{description}' => $response->description]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_actions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/actions/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
