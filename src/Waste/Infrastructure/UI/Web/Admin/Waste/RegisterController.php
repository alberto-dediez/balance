<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use App\Security\Application\Action;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\RegisterWasteType;
use App\Waste\Application\Waste\Register\RegisterWasteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/wastes/register', name: 'admin_wastes_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterWasteService $registerWaste, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Waste(WasteId::generate()));

        $form = $this->createForm(RegisterWasteType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerWaste, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerWaste($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Tipología creada correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_wastes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/wastes/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
