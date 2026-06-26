<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport;

use App\CarbonFootprint\Application\RoadTransport\Update\EmissionFactorData;
use App\CarbonFootprint\Application\RoadTransport\Update\UpdateRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Update\UpdateRoadTransportService;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateRoadTransportType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/road-transports/edit/{roadTransportId}', name: 'admin_road_transports_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetRoadTransportFromIdTrait;

    public function __invoke(
        string $roadTransportId,
        RoadTransportRepository $roadTransportRepository,
        UpdateRoadTransportService $updateRoadTransport,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $roadTransport = $this->getRoadTransportFromId($roadTransportId, $roadTransportRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $roadTransport);

        $emissionFactors = [];
        foreach ($roadTransport->emissionFactors() as $roadTransportEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($roadTransportEmissionFactor->emissionFactor());
        }

        $roadTransportRequest = new UpdateRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
            name: $roadTransport->name(),
            unit: $roadTransport->unit(),
            vehicle: $roadTransport->vehicle(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateRoadTransportType::class, $roadTransportRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateRoadTransport, $loggedUser) {
                $roadTransportRequest = $form->getData();

                $updateRoadTransport($roadTransportRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Transporte por carretera actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_road_transports');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/road_transports/edit.html.twig', [
            'roadTransport' => $roadTransport,
            'form' => $form->createView(),
        ]);
    }
}
