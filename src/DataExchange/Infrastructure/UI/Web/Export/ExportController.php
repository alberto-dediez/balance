<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Export;

use App\DataExchange\Application\Exportation\Register\RegisterExportationRequest;
use App\DataExchange\Application\Exportation\Register\RegisterExportationService;
use App\DataExchange\Infrastructure\UI\Web\DTO\ExportOptionsRequest;
use App\DataExchange\Infrastructure\UI\Web\Form\ExportOptionsType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/export', name: 'export')]
class ExportController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $code,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        RegisterExportationService $registerExportation,
        Request $request,
    ): Response {
        $exportationType = $this->getExportationTypeOrNotFound($code);

        $this->denyUnlessTypeAllowed($exportationType, $loggedUser());

        $defaultColumns = [];
        foreach ($exportationType->columnDefinitions() as $definition) {
            if ($definition->selectedByDefault) {
                $defaultColumns[] = $definition->key;
            }
        }

        $optionsForm = $this->createForm(
            ExportOptionsType::class,
            new ExportOptionsRequest(
                columns: $defaultColumns,
                filter: $exportationType->createFilter(),
            ),
            [
                'definitions' => $exportationType->columnDefinitions(),
                'filter_form_class' => $exportationType->filterFormClass(),
            ],
        );

        $optionsForm->handleRequest($request);

        if ($optionsForm->isSubmitted() && $optionsForm->isValid()) {
            /** @var ExportOptionsRequest $data */
            $data = $optionsForm->getData();

            try {
                $registerExportation(
                    new RegisterExportationRequest(
                        type: $exportationType->code(),
                        format: $data->format,
                        columns: $data->columns,
                        filter: $data->filter ?? $exportationType->createFilter(),
                        accountId: $loggedAccount()?->id(),
                    ),
                    $loggedUser(),
                );
            } catch (\Throwable $e) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido iniciar la exportación.'));

                return $this->redirectToRoute('export', ['code' => $exportationType->code()]);
            }

            $this->addFlash('success', new TranslatableMessage('La exportación se está generando. Recibirás un email con el enlace de descarga cuando esté lista.'));

            return $this->render('@data_exchange/export/ready.html.twig', [
                'exportationType' => $exportationType,
            ]);
        }

        return $this->render('@data_exchange/export/export.html.twig', [
            'exportationType' => $exportationType,
            'optionsForm' => $optionsForm->createView(),
        ]);
    }
}
