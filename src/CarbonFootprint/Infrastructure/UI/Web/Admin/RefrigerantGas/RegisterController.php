<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas;

use App\CarbonFootprint\Application\RefrigerantGas\Register\RegisterRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterRefrigerantGasType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/refrigerant-gases/register', name: 'admin_refrigerant_gases_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterRefrigerantGasService $registerRefrigerantGas, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new RefrigerantGas(RefrigerantGasId::generate()));

        $form = $this->createForm(RegisterRefrigerantGasType::class);

        try {
            $refrigerantGasId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerRefrigerantGas, $loggedUser, &$refrigerantGasId) {
                $registerRequest = $form->getData();

                $response = $registerRefrigerantGas($registerRequest, $loggedUser());

                $refrigerantGasId = $response->refrigerantGasId;

                $this->addFlash('success', new TranslatableMessage('Gas refrigerante creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($refrigerantGasId instanceof RefrigerantGasId) {
                    return $this->redirectToRoute('admin_refrigerant_gases_edit', ['refrigerantGasId' => $refrigerantGasId]);
                }

                return $this->redirectToRoute('admin_refrigerant_gases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/refrigerant_gases/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
