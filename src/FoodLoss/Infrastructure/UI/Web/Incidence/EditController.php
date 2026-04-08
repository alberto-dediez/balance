<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\Update\UpdateIncidenceRequest;
use App\FoodLoss\Application\Incidence\Update\UpdateIncidenceService;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateIncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/incidences/edit/{incidenceId}', name: 'incidences_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetIncidenceFromIdTrait;

    public function __invoke(
        string $incidenceId,
        IncidenceRepository $incidenceRepository,
        UpdateIncidenceService $updateIncidence,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $incidence = $this->getIncidenceFromId($incidenceId, $incidenceRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $incidence);

        $exceptionOccurredOn = $incidence->exceptionOccurredOn();
        $incidenceType = $incidence->incidenceType();
        $propertyId = $incidence->propertyId();

        assert($propertyId instanceof PropertyId);

        $incidenceRequest = new UpdateIncidenceRequest(
            incidenceId: $incidence->id(),
            name: $incidence->name(),
            exceptionOccurredOn: $exceptionOccurredOn,
            incidenceType: $incidenceType,
            description: $incidence->description(),
            effect: $incidence->effect(),
            propertyId: $propertyId,
        );

        try {
            $form = $this->createForm(UpdateIncidenceType::class, $incidenceRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateIncidence, $loggedUser) {
                $incidenceRequest = $form->getData();

                $updateIncidence($incidenceRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Incidencia actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('incidences');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/incidences/edit.html.twig', [
            'incidence' => $incidence,
            'form' => $form->createView(),
        ]);
    }
}
