<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Application\Importation\Register\RegisterImportationRequest;
use App\DataExchange\Application\Importation\Register\RegisterImportationResponse;
use App\DataExchange\Infrastructure\UI\Web\DTO\ImportFileRequest;
use App\DataExchange\Infrastructure\UI\Web\Form\ImportFileType;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Proceso completo:
 * ===========================================
 *
 * 1) Subir fichero indicando lineas a ignorar
 * 2) Convertir fichero en su propia base sqlite para almacenarlo y registrar "proceso de importacion"
 * 3) Sobre dicho proceso, indicar campos registrados y emparejarlos con los campos esperados
 * 4a) Sobre los campos no emparejados, mostrar valores posibles para que se escoja el valor para cada campo
 * 4b) Sobre los campos emparejados, mostrar valores para emparejar con posibles valores
 * 5) ¿Importar en segundo plano avisando via email?
 */
#[Route('/measurements/import', name: 'measurements_import')]
class ImportController extends AbstractController
{
    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        MeasurementAuthorizer $measurementAuthorizer,
        Request $request,
    ): Response
    {
        if (false === $measurementAuthorizer->canDoThat(
            new Measurement(
                id: MeasurementId::generate(),
                accountId: $loggedAccount()?->id()
            ),
            Action::Import,
            $loggedUser()
        )) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ImportFileType::class, new ImportFileRequest());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var RegisterImportationResponse|null $response */
            $response = CommandBusDispatcher::instance()->dispatch(
                new RegisterImportationRequest(
                    name: $data->file->getClientOriginalName(),
                    path: $data->file->getRealPath(),
                    offset: $data->offset,
                    extension: $data->file->guessExtension(),
                    accountId: $loggedAccount()?->id()
                ), $loggedUser()
            );

            if (null === $response || null === $response->importationId) {
                $this->addFlash('danger', new TranslatableMessage('Error importando el fichero {file}', ['{file}' => $data->file->getClientOriginalName()]));

                return $this->redirectToRoute('measurements');
            }

            $this->addFlash('success', new TranslatableMessage('Fichero {file} subido con éxito', ['{file}' => $data->file->getClientOriginalName()]));

            return $this->redirectToRoute('measurements_import_pair_fields', ['id' => (string) $response->importationId]);
        }

        return $this->render('@waste/measurements/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
