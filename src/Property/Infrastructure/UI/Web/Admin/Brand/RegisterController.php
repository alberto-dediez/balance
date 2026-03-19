<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Security\Application\Action;
use App\Property\Domain\Model\Brand\Brand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Brand\BrandId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Brand\Register\RegisterBrandService;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterBrandType;

#[Route('/admin/brands/register', name: 'admin_brands_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterBrandService $registerBrand, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Brand(BrandId::generate()));

        $form = $this->createForm(AdminRegisterBrandType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerBrand, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerBrand($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Marca creada correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_brands');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/brands/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
