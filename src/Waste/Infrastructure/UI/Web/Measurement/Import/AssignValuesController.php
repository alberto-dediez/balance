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
        $importationId = new ImportationId($id);
        $importation = $importationRepository->find($importationId);

        $this->denyAccessUnlessGrantedToView($importation);

        $definitions = $this->getDefinitions($translator);

        $notConfiguredFields = array_filter(array_keys($definitions), function ($field) use ($importation) {
            return null === $importation->configurationForField($field);
        });

        if (count($notConfiguredFields) === 0) {
            return $this->redirectToRoute('measurements_import_finish', ['id' => $importation->id()]);
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
                    importationId: $importationId,
                    defaultValues: $form->getData(),
                ), $this->loggedUser->__invoke()
            );

            if (null === $response) {
                $this->addFlash('danger', new TranslatableMessage('Hay errores en la configuración de los distintos campos. Por favor, revise el formulario.'));
            } else {
                return $this->redirectToRoute('measurements_import_finish', ['id' => $importation->id()]);
            }
        }

        return $this->render('@waste/measurements/import/assign-values.html.twig', [
            'form' => $form->createView(),
            'notConfiguredFields' => $notConfiguredFields,
        ]);
    }

    private function getDefinitions(TranslatorInterface $translator): array
    {
        $unitChoicesForImportation = array_filter(Unit::cases(), fn ($unit) => $unit !== Unit::Percentage);

        return [
            'containerId' => new TranslationDefinition(
                fieldType: ContainerIdType::class
            ),
            'propertyId' =>  new TranslationDefinition(
                fieldType: PropertyIdType::class
            ),
            'wasteId' => new TranslationDefinition(
                fieldType: WasteIdType::class
            ),
            'purpose' =>  new TranslationDefinition(
                fieldType: PurposeType::class
            ),
            'wasteManagerId' =>  new TranslationDefinition(
                fieldType: WasteManagerIdType::class
            ),
            'quantity' => null,
            'load' => null,
            'justification' => new TranslationDefinition(
                fieldType: TextType::class
            ),
            'unit' => new TranslationDefinition(
                fieldType: ChoiceType::class,
                options: [
                    'choices' => array_combine(
                        array_map(fn (Unit $unit) => $unit->label($translator), $unitChoicesForImportation),
                        array_map(fn (Unit $unit) => $unit->value, $unitChoicesForImportation),
                    ),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Ignorar'),
                ]
            ),
            'day' => new TranslationDefinition(
                fieldType: DateTimeType::class
            ),
            'reference' => new TranslationDefinition(
                fieldType: TextType::class
            ),
        ];
    }
}
