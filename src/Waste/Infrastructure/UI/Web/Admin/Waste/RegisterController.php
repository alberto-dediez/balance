<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Infrastructure\Symfony\Form\RegisterWasteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Waste\Register\RegisterWasteService;

#[Route('/admin/wastes/register', name: 'admin_wastes_register')]
#[IsGranted("ROLE_ADMIN")]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterWasteService $registerWaste, Request $request): Response
    {   
        $form = $this->createForm(RegisterWasteType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerWaste) {
            $registerRequest = $form->getData();

            $response = $registerWaste($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Residuo creado correctamente: {name}", ['{name}' => $response->name]));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_wastes');
        }

        return $this->render('@waste/admin/wastes/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
