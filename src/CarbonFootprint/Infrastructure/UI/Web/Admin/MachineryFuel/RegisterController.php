<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MachineryFuel;

use App\CarbonFootprint\Application\MachineryFuel\Register\RegisterMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMachineryFuelType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/machinery-fuels/register', name: 'admin_machinery_fuels_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMachineryFuelService $registerMachineryFuel, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MachineryFuel(MachineryFuelId::generate()));

        $form = $this->createForm(RegisterMachineryFuelType::class);

        try {
            $machineryFuelId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMachineryFuel, $loggedUser, &$machineryFuelId) {
                $registerRequest = $form->getData();

                $response = $registerMachineryFuel($registerRequest, $loggedUser());

                $machineryFuelId = $response->machineryFuelId;

                $this->addFlash('success', new TranslatableMessage('Combustible de maquinaria creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($machineryFuelId instanceof MachineryFuelId) {
                    return $this->redirectToRoute('admin_machinery_fuels_edit', ['machineryFuelId' => $machineryFuelId]);
                }

                return $this->redirectToRoute('admin_machinery_fuels');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/machinery_fuels/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
