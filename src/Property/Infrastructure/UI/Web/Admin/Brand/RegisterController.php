<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Property\Application\Brand\Register\RegisterBrandService;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterBrandType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

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

                $this->addFlash('success', new TranslatableMessage('Marca creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_brands');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/brands/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
