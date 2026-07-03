<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationResponse;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationService;
use App\DataExchange\Application\ImportationProfile\Recall\RecallImportationChoicesRequest;
use App\DataExchange\Application\ImportationProfile\Recall\RecallImportationChoicesService;
use App\DataExchange\Infrastructure\UI\Web\Form\PairFieldsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/import/{id}/pair-fields', name: 'import_pair_fields')]
class PairFieldsController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        string $id,
        LoggedUser $loggedUser,
        PairFieldsImportationService $pairFields,
        RecallImportationChoicesService $recallChoices,
        Request $request,
    ): Response {
        $importationType = $this->getImportationTypeOrNotFound($code);

        $importation = $this->getImportationOrNotFound($id);

        $this->denyAccessUnlessUserIsGranted($loggedUser(), $importation, $importationType);

        $pairFieldsRequest = new PairFieldsImportationRequest($importation->id(), $importation->configurations());

        if (0 === count($pairFieldsRequest->pairedConfigurations())) {
            $recalled = $recallChoices(new RecallImportationChoicesRequest($importation->id()), $loggedUser());
            $pairFieldsRequest = new PairFieldsImportationRequest($importation->id(), $recalled->configurations);
        }

        $form = $this->createForm(PairFieldsType::class, $pairFieldsRequest, [
            'fields' => array_combine($importationType->fields(), $importationType->fields()),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $response = $pairFields(
                    $form->getData(),
                    $loggedUser(),
                );
            } catch (\Throwable) {
                $response = null;
            }

            if (!$response instanceof PairFieldsImportationResponse) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en el emparejamiento. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('import_translations', ['code' => $importationType->code(), 'id' => $importation->id()]);
            }
        }

        return $this->render('@data_exchange/import/pair_fields.html.twig', [
            'form' => $form->createView(),
            'importation' => $importation,
            'importationType' => $importationType,
        ]);
    }
}
