<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\IntensityDenominatorsType;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/results/activity-data', name: 'company_carbon_footprints_declare_denominators', methods: ['POST'])]
class DeclareIntensityDenominatorsController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        DeclareIntensityDenominatorsService $declareIntensityDenominators,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        $declareRequest = new DeclareIntensityDenominatorsRequest($companyCarbonFootprint->id());

        $form = $this->createForm(IntensityDenominatorsType::class, $declareRequest, ['data_class' => DeclareIntensityDenominatorsRequest::class]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            $this->addFlash('danger', count($errors) > 0 ? $errors->current()->getMessage() : new TranslatableMessage('No se han podido guardar los datos de actividad.'));

            return $this->redirectToResults($companyCarbonFootprintId);
        }

        try {
            $declareIntensityDenominators($declareRequest, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('success', new TranslatableMessage('Datos de actividad guardados.'));

        return $this->redirectToResults($companyCarbonFootprintId);
    }

    private function redirectToResults(string $companyCarbonFootprintId): Response
    {
        return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
    }
}
