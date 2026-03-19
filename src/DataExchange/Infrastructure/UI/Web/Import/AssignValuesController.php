<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\AssignValues\AssignValuesImportationRequest;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Infrastructure\UI\Web\Form\AssignValuesType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/import/{id}/assign-values', name: 'import_assign_values')]
class AssignValuesController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        string $id,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $importationType = $this->getImportationTypeOrNotFound($code);

        $importation = $this->getImportationOrNotFound($id);

        $this->denyAccessUnlessUserIsGranted($loggedUser(), $importation, $importationType);

        $definitions = $importationType->assignValueDefinitions();

        $notConfiguredFields = array_filter(array_keys($definitions), fn(int|string $field) => !$importation->configurationForField($field) instanceof ImportationColumnConfiguration);

        if (0 === count($notConfiguredFields)) {
            return $this->redirectToRoute('import_finish', ['code' => $code, 'id' => $importation->id()]);
        }

        $defaultValues = $importation->defaultValues();

        $form = $this->createForm(AssignValuesType::class, $defaultValues, [
            'fields' => $notConfiguredFields,
            'definitions' => $definitions,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = CommandBusDispatcher::instance()->dispatch(
                new AssignValuesImportationRequest(
                    importationId: $importation->id(),
                    defaultValues: $form->getData(),
                ),
                $loggedUser(),
            );

            if (!$response instanceof CommandResponse) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en la configuración de los distintos campos. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('import_finish', ['code' => $code, 'id' => $importation->id()]);
            }
        }

        return $this->render('@data_exchange/import/assign-values.html.twig', [
            'form' => $form->createView(),
            'notConfiguredFields' => $notConfiguredFields,
            'importation' => $importation,
            'importationType' => $importationType,
        ]);
    }
}
