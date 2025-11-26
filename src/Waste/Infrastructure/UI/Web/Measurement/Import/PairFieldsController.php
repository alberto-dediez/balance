<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Application\Importation\ImportationAuthorizer;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\UI\Web\Form\PairFieldsType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/measurements/import/{id}/pair-fields', name: 'measurements_import_pair_fields')]
class PairFieldsController extends AbstractController
{
    use AuthorizeTrait;

    public function __construct(
        private LoggedUser $loggedUser,
        private LoggedAccount $loggedAccount,
        private MeasurementAuthorizer $measurementAuthorizer,
        private ImportationAuthorizer $importationAuthorizer,
    ) {

    }

    public function __invoke(
        string $id,
        ImportationRepository $importationRepository,
        Request $request,
    ): Response
    {
        $importationId = new ImportationId($id);
        $importation = $importationRepository->find($importationId);

        $this->denyAccessUnlessGrantedToView($importation);

        $pairFieldsRequest = new PairFieldsImportationRequest($importation->id(), $importation->configurations());

        $form = $this->createForm(PairFieldsType::class, $pairFieldsRequest, [
            'fields' => [
                'containerId' => 'containerId',
                'propertyId' => 'propertyId',
                'wasteId' => 'wasteId',
                'purpose' => 'purpose',
                'wasteManagerId' => 'wasteManagerId',
                'quantity' => 'quantity',
                'load' => 'load',
                'justification' => 'justification',
                'unit' => 'unit',
                'day' => 'day',
            ]
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $response = CommandBusDispatcher::instance()->dispatch(
                    $form->getData(), $this->loggedUser->__invoke()
                );
            } catch (\Throwable $exception) {
                $response = null;
            }

            if (null === $response) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en el emparejamiento. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('measurements_import_translations', ['id' => $importation->id()]);
            }
        }

        return $this->render('@waste/measurements/import/pair_fields.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
