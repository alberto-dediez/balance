<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Infrastructure\Symfony\Form\ImportMeasurementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        $form = $this->createForm(ImportMeasurementType::class, new ImportMeasurementRequest());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            //Crear proceso de importacion
            //Almacenar fichero pasado en formato sqlite propio
            // - Crear fichero con nombre (id importacion)
            // - Obtener columnas en base a la cabecera
            // - Importar datos a esta base de datos
            //Redireccionar al siguiente paso
        }

        return $this->render('@waste/measurements/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
