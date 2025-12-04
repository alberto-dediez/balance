<?php

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationService;
use App\DataExchange\Infrastructure\UI\Web\Form\TranslationsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/import/{id}/translations', name: 'import_translations')]
class TranslationsController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        string $id,
        LoggedUser $loggedUser,
        PairFieldsImportationService $pairFields,
        Request $request,
    ): Response
    {
        $importationType = $this->getImportationTypeOrNotFound($code);

        $importation = $this->getImportationOrNotFound($id);

        $this->denyAccessUnlessUserIsGranted($loggedUser(), $importation, $importationType);

        $pairFieldsRequest = new PairFieldsImportationRequest($importation->id(), $importation->configurations());

        if (count($pairFieldsRequest->pairedConfigurations()) === 0) {
            return $this->redirectToRoute('import_assign_values', ['code' => $code, 'id' => $importation->id()]);
        }

        $form = $this->createForm(TranslationsType::class, $pairFieldsRequest, [
            'definitions' => $importationType->translationDefinitions()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $pairFields(
                $form->getData(), $loggedUser()
            );

            if (null === $response) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en la configuración de los distintos campos. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('import_assign_values', ['code' => $code, 'id' => $importation->id()]);
            }
        }

        return $this->render('@data_exchange/import/translations.html.twig', [
            'form' => $form->createView(),
            'importation' => $importation,
            'importationType' => $importationType,
        ]);
    }
}
