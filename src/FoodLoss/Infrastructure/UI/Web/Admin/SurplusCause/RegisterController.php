<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause;

use App\FoodLoss\Application\SurplusCause\Register\RegisterSurplusCauseService;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterSurplusCauseType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/surplus-causes/register', name: 'admin_surplus_causes_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterSurplusCauseService $registerSurplusCause, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::Register->value, new SurplusCause(SurplusCauseId::generate()));

        $form = $this->createForm(RegisterSurplusCauseType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerSurplusCause, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerSurplusCause($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Causa de excedente creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_surplus_causes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/surplus-causes/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
