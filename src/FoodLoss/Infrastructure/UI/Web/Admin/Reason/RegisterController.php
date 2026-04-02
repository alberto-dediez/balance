<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason;

use App\FoodLoss\Application\Reason\Register\RegisterReasonService;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterReasonType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/reasons/register', name: 'admin_reasons_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterReasonService $registerReason, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Reason(ReasonId::generate()));

        $form = $this->createForm(RegisterReasonType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerReason, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerReason($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Motivo creado correctamente: {description}', ['{description}' => $response->description]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_reasons');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/reasons/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
