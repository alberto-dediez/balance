<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport;

use App\CarbonFootprint\Application\RoadTransport\Register\RegisterRoadTransportService;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterRoadTransportType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/road-transports/register', name: 'admin_road_transports_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterRoadTransportService $registerRoadTransport, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new RoadTransport(RoadTransportId::generate()));

        $form = $this->createForm(RegisterRoadTransportType::class);

        try {
            $roadTransportId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerRoadTransport, $loggedUser, &$roadTransportId) {
                $registerRequest = $form->getData();

                $response = $registerRoadTransport($registerRequest, $loggedUser());

                $roadTransportId = $response->roadTransportId;

                $this->addFlash('success', new TranslatableMessage('Transporte por carretera creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($roadTransportId instanceof RoadTransportId) {
                    return $this->redirectToRoute('admin_road_transports_edit', ['roadTransportId' => $roadTransportId]);
                }

                return $this->redirectToRoute('admin_road_transports');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/road_transports/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
