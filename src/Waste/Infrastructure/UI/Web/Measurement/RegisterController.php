<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\Symfony\Form\RegisterMeasurementType;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;

#[Route('/measurements/register', name: 'measurements_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        RegisterMeasurementService $registerMeasurement, 
        Request $request
    ): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Measurement(MeasurementId::generate()));

        $form = $this->createForm(RegisterMeasurementType::class);

        $user = $loggedUser();

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerMeasurement, $user) {
            $registerRequest = new RegisterMeasurementRequest(
                containerId: $form->get('containerId')->getData(),                
                quantity: $form->get('quantity')->getData(),
                load: $form->get('load')->getData(),
                registeredBy: $user->id(),
                justification: $form->get('justification')->getData()
            );

            $response = $registerMeasurement($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Medición registrada correctamente"));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('measurements');
        }

        return $this->render('@waste/measurements/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
