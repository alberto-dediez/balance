<?php

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\Execute\ExecuteImportationRequest;
use App\DataExchange\Application\Importation\Execute\ExecuteImportationService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/import/{id}/finish', name: 'import_finish')]
class FinishController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        string $id,
        LoggedUser $loggedUser,
        ExecuteImportationService $executeImportation,
        Request $request,
    ): Response
    {
        $importationType = $this->getImportationTypeOrNotFound($code);

        $importation = $this->getImportationOrNotFound($id);

        $this->denyAccessUnlessUserIsGranted($loggedUser(), $importation, $importationType);

        try {
            $executeImportation(new ExecuteImportationRequest(importationId: $importation->id()), $loggedUser());
        } catch (\Throwable $throwable) {
            $this->addFlash('danger', new TranslatableMessage('No se ha importado el fichero correctamente. Revise la configuración o contacte con un administrador.'));

            return $this->redirectToRoute($importationType->returnRoute());
        }

        return $this->redirectToRoute('import_view', ['code' => $code, 'id' => $id]);
    }
}
