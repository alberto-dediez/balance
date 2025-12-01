<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\UI\Web\DTO\TranslationDefinition;
use App\DataExchange\Infrastructure\UI\Web\Form\TranslationsType;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/measurements/import/{id}/assign-values', name: 'measurements_import_assign_values')]
class AssignValuesController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $id,
        ImportationRepository $importationRepository,
        TranslatorInterface $translator,
        Request $request,
    ): Response
    {
        /*$importationId = new ImportationId($id);
        $importation = $importationRepository->find($importationId);

        $this->denyAccessUnlessGrantedToView($importation);

        $pairFieldsRequest = new PairFieldsImportationRequest($importation->id(), $importation->configurations());

        if (count($pairFieldsRequest->pairedConfigurations()) === 0) {
            //Redirigir al próximo paso
        }

        $form = $this->createForm(TranslationsType::class, $pairFieldsRequest, [
            'definitions' => $this->getDefinitions($translator)
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = CommandBusDispatcher::instance()->dispatch(
                $form->getData(), $this->loggedUser->__invoke()
            );

            if (null === $response) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en la configuración de los distintos campos. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('measurements_import_translations', ['id' => $importation->id()]);
            }
        }

        return $this->render('@waste/measurements/import/translations.html.twig', [
            'form' => $form->createView(),
        ]);*/

        return $this->render('@waste/measurements/import/assign-values.html.twig', []);
    }
}
