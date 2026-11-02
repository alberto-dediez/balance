<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\IntensityDenominatorsType;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/results/activity-data', name: 'property_carbon_footprints_declare_denominators', methods: ['POST'])]
class DeclareIntensityDenominatorsController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        DeclareIntensityDenominatorsService $declareIntensityDenominators,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        $declareRequest = new DeclareIntensityDenominatorsRequest($propertyCarbonFootprint->id());

        $form = $this->createForm(IntensityDenominatorsType::class, $declareRequest, ['data_class' => DeclareIntensityDenominatorsRequest::class]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            $this->addFlash('danger', count($errors) > 0 ? $errors->current()->getMessage() : new TranslatableMessage('No se han podido guardar los datos de actividad.'));

            return $this->redirectToResults($propertyCarbonFootprintId);
        }

        try {
            $declareIntensityDenominators($declareRequest, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('success', new TranslatableMessage('Datos de actividad guardados.'));

        return $this->redirectToResults($propertyCarbonFootprintId);
    }

    private function redirectToResults(string $propertyCarbonFootprintId): Response
    {
        return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
    }
}
