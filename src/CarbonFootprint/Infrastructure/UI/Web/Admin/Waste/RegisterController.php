<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste;

use App\CarbonFootprint\Application\Waste\Register\RegisterWasteService;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterWasteType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/wastes/register', name: 'admin_wastes_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterWasteService $registerWaste, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Waste(WasteId::generate()));

        $form = $this->createForm(RegisterWasteType::class);

        try {
            $wasteId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerWaste, $loggedUser, &$wasteId) {
                $registerRequest = $form->getData();

                $response = $registerWaste($registerRequest, $loggedUser());

                $wasteId = $response->wasteId;

                $this->addFlash('success', new TranslatableMessage('Residuo creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($wasteId instanceof WasteId) {
                    return $this->redirectToRoute('admin_wastes_edit', ['wasteId' => $wasteId]);
                }

                return $this->redirectToRoute('admin_wastes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/wastes/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
