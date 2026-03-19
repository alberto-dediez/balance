<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Infrastructure\Symfony\Form\RegisterMeasurementType;
use App\Waste\Infrastructure\UI\Web\Container\GetContainerFromIdAndAccountTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

class RegisterController extends AbstractController
{
    use RegisterTrait;
    use GetContainerFromIdAndAccountTrait;

    #[Route('/measurements/register', name: 'measurements_register')]
    public function chooseContainer(
        LoggedUser $loggedUser,
        ContainerRepository $containerRepository,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Register->value, new Measurement(
            id: MeasurementId::generate(),
            accountId: $loggedUser()->accountId(),
        ));

        $containers = $containerRepository->findByFilter(
            new ContainerFilter(
                accountId: $loggedUser()->accountId(),
                limit: 0,
            ),
        );

        return $this->render('@waste/measurements/choose-container.html.twig', [
            'containers' => $containers,
        ]);
    }

    #[Route('/measurements/register/{containerId}', name: 'measurements_register_for_container')]
    public function registerForContainer(
        string $containerId,
        ContainerRepository $containerRepository,
        LoggedUser $loggedUser,
        RegisterMeasurementService $registerMeasurement,
        Request $request,
    ): Response {
        $container = $this->getContainerFromIdAndAccount($containerId, $loggedUser()->accountId(), $containerRepository);

        $this->denyAccessUnlessGranted(Action::Register->value, new Measurement(
            id: MeasurementId::generate(),
            accountId: $loggedUser()->accountId(),
        ));

        $registerRequest = new RegisterMeasurementRequest(
            containerId: $container->id(),
            quantity: 1,
            load: 0,
            registeredBy: $loggedUser()->id(),
            day: new \DateTimeImmutable('NOW'),
        );

        $form = $this->createForm(RegisterMeasurementType::class, $registerRequest, ['measurementMethod' => $container->measurementMethod()]);

        $user = $loggedUser();

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMeasurement, $user): true {
                $registerRequest = $form->getData();

                $response = $registerMeasurement($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Medición registrada correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('measurements');
            }
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/measurements/register.html.twig', [
            'form' => $form->createView(),
            'container' => $container,
        ]);
    }
}
