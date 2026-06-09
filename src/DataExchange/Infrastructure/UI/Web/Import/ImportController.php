<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\DataExchange\Application\Importation\Register\RegisterImportationRequest;
use App\DataExchange\Application\Importation\Register\RegisterImportationResponse;
use App\DataExchange\Application\Importation\Register\RegisterImportationService;
use App\DataExchange\Domain\Model\Importation\ImportationType;
use App\DataExchange\Domain\Model\Importation\ImportationTypeFactory;
use App\DataExchange\Infrastructure\UI\Web\DTO\ImportFileRequest;
use App\DataExchange\Infrastructure\UI\Web\Form\ImportFileType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{code}/import', name: 'import')]
class ImportController extends AbstractController
{
    public function __invoke(
        string $code,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ImportationTypeFactory $importationTypeFactory,
        RegisterImportationService $registerImportation,
        Request $request,
    ): Response {
        $importationType = $importationTypeFactory->createFromCode($code);

        if (!$importationType instanceof ImportationType) {
            throw $this->createNotFoundException();
        }

        if (false === $importationType->isAuthorized($loggedUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ImportFileType::class, new ImportFileRequest());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ImportFileRequest $data */
            $data = $form->getData();

            if (!$data->file instanceof UploadedFile) {
                $this->addFlash('danger', new TranslatableMessage('El fichero no es válido.'));

                return $this->redirectToRoute($importationType->returnRoute());
            }

            try {
                $response = $registerImportation(
                    new RegisterImportationRequest(
                        name: (string) $data->file->getClientOriginalName(),
                        type: $importationType->code(),
                        path: (string) $data->file->getRealPath(),
                        offset: $data->offset,
                        extension: (string) $data->file->guessExtension(),
                        accountId: $loggedAccount()?->id(),
                    ),
                    $loggedUser(),
                );
            } catch (\Throwable) {
                $response = null;
            }

            if (!$response instanceof RegisterImportationResponse) {
                $this->addFlash('danger', new TranslatableMessage('Error importando el fichero {file}', ['{file}' => (string) $data->file->getClientOriginalName()]));

                return $this->redirectToRoute($importationType->returnRoute());
            }

            $this->addFlash('success', new TranslatableMessage('Fichero {file} subido con éxito', ['{file}' => (string) $data->file->getClientOriginalName()]));

            return $this->redirectToRoute('import_pair_fields', ['code' => $importationType->code(), 'id' => (string) $response->importationId]);
        }

        return $this->render('@data_exchange/import/import.html.twig', [
            'form' => $form->createView(),
            'importationType' => $importationType,
        ]);
    }
}
