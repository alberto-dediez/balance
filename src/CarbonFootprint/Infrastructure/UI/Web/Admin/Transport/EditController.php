<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Transport;

use App\CarbonFootprint\Application\Transport\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Transport\Update\UpdateTransportRequest;
use App\CarbonFootprint\Application\Transport\Update\UpdateTransportService;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateTransportType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/transports/edit/{transportId}', name: 'admin_transports_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetTransportFromIdTrait;

    public function __invoke(
        string $transportId,
        TransportRepository $transportRepository,
        UpdateTransportService $updateTransport,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $transport = $this->getTransportFromId($transportId, $transportRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $transport);

        $emissionFactors = [];
        foreach ($transport->emissionFactors() as $transportEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($transportEmissionFactor->emissionFactor());
        }

        $transportRequest = new UpdateTransportRequest(
            transportId: $transport->id(),
            name: $transport->name(),
            unit: $transport->unit(),
            vehicle: $transport->vehicle(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateTransportType::class, $transportRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateTransport, $loggedUser) {
                $transportRequest = $form->getData();

                $updateTransport($transportRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Medio de transporte actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_transports');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/transports/edit.html.twig', [
            'transport' => $transport,
            'form' => $form->createView(),
        ]);
    }
}
