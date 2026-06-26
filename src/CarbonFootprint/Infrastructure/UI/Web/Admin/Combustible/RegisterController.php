<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible;

use App\CarbonFootprint\Application\Combustible\Register\RegisterCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterCombustibleType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/combustibles/register', name: 'admin_combustibles_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCombustibleService $registerCombustible, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Combustible(CombustibleId::generate()));

        $form = $this->createForm(RegisterCombustibleType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCombustible, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerCombustible($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Combustible creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_combustibles');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/combustibles/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
