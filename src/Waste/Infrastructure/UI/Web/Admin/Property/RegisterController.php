<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Property\Register\RegisterPropertyService;
use App\Waste\Infrastructure\Symfony\Form\AdminRegisterPropertyType;

#[Route('/admin/properties/register', name: 'admin_properties_register')]
#[IsGranted("ROLE_ADMIN")]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterPropertyService $registerProperty, Request $request): Response
    {   
        $form = $this->createForm(AdminRegisterPropertyType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerProperty) {
            $registerRequest = $form->getData();

            $account = $form->get('account')->getData();

            if (null !== $account) {
                $registerRequest->accountId = $account->id();
            }

            $response = $registerProperty($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Propiedad creada correctamente: {name}", ['{name}' => $response->name]));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_properties');
        }

        return $this->render('@waste/admin/properties/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
