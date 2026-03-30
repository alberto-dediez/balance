<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\Import;

use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{code}/import/{id}/results', name: 'import_view')]
class ViewController extends AbstractController
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

        $this->importationAuthorizer->denyUnlessCanDoThat($importation, Action::View, $loggedUser());

        $results = [];

        $rowNumber = 0;
        while (null !== ($row = $this->importationRepository->getResult($importation->id(), $rowNumber))) {
            $results[] = $row;

            ++$rowNumber;
        }

        return $this->render('@data_exchange/import/view.html.twig', [
            'importation' => $importation,
            'importationType' => $importationType,
            'results' => $results,
        ]);
    }
}
