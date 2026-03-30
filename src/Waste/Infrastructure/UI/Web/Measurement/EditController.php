<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Application\Measurement\Update\UpdateMeasurementService;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Infrastructure\Symfony\Form\UpdateMeasurementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/measurements/edit/{measurementId}', name: 'measurements_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMeasurementFromIdTrait;

    public function __invoke(
        string $measurementId,
        MeasurementRepository $measurementRepository,
        UpdateMeasurementService $updateMeasurement,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $measurement = $this->getMeasurementFromId($measurementId, $measurementRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $measurement);

        $measurementRequest = new UpdateMeasurementRequest(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
            reference: $measurement->reference(),
        );

        try {
            $form = $this->createForm(UpdateMeasurementType::class, $measurementRequest, ['unit' => $measurement->unit()]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMeasurement, $loggedUser): void {
                $measurementRequest = $form->getData();

                $updateMeasurement($measurementRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Medición actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('measurements');
            }
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/measurements/edit.html.twig', [
            'measurement' => $measurement,
            'form' => $form->createView(),
        ]);
    }
}
