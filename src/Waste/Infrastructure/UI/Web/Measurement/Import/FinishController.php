<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use App\DataExchange\Application\Importation\AssignValues\AssignValuesImportationRequest;
use App\DataExchange\Application\Importation\PairFields\PairFieldsImportationRequest;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Infrastructure\UI\Web\DTO\TranslationDefinition;
use App\DataExchange\Infrastructure\UI\Web\Form\AssignValuesType;
use App\DataExchange\Infrastructure\UI\Web\Form\TranslationsType;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Infrastructure\Symfony\Form\Type\ContainerIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/measurements/import/{id}/finish', name: 'measurements_import_finish')]
class FinishController extends AbstractController
{
    use AuthorizeTrait;

    public function __invoke(
        string $id,
        ImportationRepository $importationRepository,
        TranslatorInterface $translator,
        Request $request,
    ): Response
    {
        $importationId = new ImportationId($id);
        $importation = $importationRepository->find($importationId);

        $this->denyAccessUnlessGrantedToView($importation);

        return $this->render('@waste/measurements/import/finish.html.twig', [
            'importation' => $importation,
        ]);
    }
}
