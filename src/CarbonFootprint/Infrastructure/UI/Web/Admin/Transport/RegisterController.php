<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Transport;

use App\CarbonFootprint\Application\Transport\Register\RegisterTransportService;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterTransportType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/transports/register', name: 'admin_transports_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterTransportService $registerTransport, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Transport(TransportId::generate()));

        $form = $this->createForm(RegisterTransportType::class);

        try {
            $transportId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerTransport, $loggedUser, &$transportId) {
                $registerRequest = $form->getData();

                $response = $registerTransport($registerRequest, $loggedUser());

                $transportId = $response->transportId;

                $this->addFlash('success', new TranslatableMessage('Medio de transporte creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($transportId instanceof TransportId) {
                    return $this->redirectToRoute('admin_transports_edit', ['transportId' => $transportId]);
                }

                return $this->redirectToRoute('admin_transports');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/transports/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
