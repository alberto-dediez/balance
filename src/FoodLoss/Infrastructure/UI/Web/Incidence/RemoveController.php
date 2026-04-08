<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\Remove\RemoveIncidenceRequest;
use App\FoodLoss\Application\Incidence\Remove\RemoveIncidenceService;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/incidences/remove/{incidenceId}', name: 'incidences_remove')]
class RemoveController extends AbstractController
{
    use GetIncidenceFromIdTrait;

    public function __invoke(
        string $incidenceId,
        IncidenceRepository $incidenceRepository,
        RemoveIncidenceService $removeIncidence,
        LoggedUser $loggedUser,
    ): Response {
        $incidence = $this->getIncidenceFromId($incidenceId, $incidenceRepository);

        try {
            $removeIncidence(new RemoveIncidenceRequest($incidence->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la incidencia {incidence}', ['{incidence}' => $incidence->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la incidencia {incidence}', ['{incidence}' => $incidence->name()]));
        }

        return $this->redirectToRoute('incidences');
    }
}
