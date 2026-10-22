<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope\AnswerPropertyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope\AnswerPropertyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope\GetPropertyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope\GetPropertyCarbonFootprintScopeService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageExclusionHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\StepExclusionsView;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/scope-3', name: 'property_carbon_footprints_scope_3')]
class Scope3Controller extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        StageAnswerHttpMapper $answerMapper,
        StageExclusionHttpMapper $exclusionMapper,
        AnswerPropertyCarbonFootprintScopeService $answerScope,
        GetPropertyCarbonFootprintScopeService $getScope,
        ScopeStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isScope2Answered()) {
            return $this->redirectToRoute(
                $propertyCarbonFootprint->status()->isScope1Answered() ? 'property_carbon_footprints_scope_2' : 'property_carbon_footprints_scope_1',
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('scope_3_' . $propertyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerScope(
                    new AnswerPropertyCarbonFootprintScopeRequest(
                        $propertyCarbonFootprint->id(),
                        Scope::Scope3,
                        $answerMapper->map($request->request->all(), Scope::Scope3->sections()),
                        $exclusionMapper->map($request, Scope::Scope3),
                        $exclusionMapper->mapStage($request, Scope::Scope3),
                    ),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException|ExchangeRateNotFoundException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('property_carbon_footprints_scope_3', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
            }

            return $this->redirectToRoute('property_carbon_footprints_mitigation_plan', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        $response = $getScope(new GetPropertyCarbonFootprintScopeRequest($propertyCarbonFootprint->id(), Scope::Scope3), $loggedUser());

        return $this->render(
            '@carbon_footprint/property_carbon_footprints/step/scope-3.html.twig',
            [
                'blocks' => [$stepView->build($response->sections) + ['property' => null, 'prefixBase' => '']],
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'readOnly' => $propertyCarbonFootprint->isCompleted(),
                'exclusions' => StepExclusionsView::build($response->exclusions),
                'exclusionReasons' => ExclusionReason::cases(),
                'calculateUrl' => $this->generateUrl('property_carbon_footprints_emissions_calculate', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]),
                'stageExclusion' => null === $response->stageExclusion ? null : [
                    'reason' => $response->stageExclusion->reason->value,
                    'comment' => $response->stageExclusion->comment,
                ],
                'derivedEmissions' => $response->derivedEmissions,
                'derivedLines' => $response->derivedLines,
                'currencies' => Unit::currencies(),
                'upstreamSources' => array_map(static fn(Source $source) => $source->value, Scope::Scope3->upstreamSources()),
                'downstreamSources' => array_map(
                    static fn(Source $source) => $source->value,
                    array_values(array_filter(
                        Scope::Scope3->downstreamSources(),
                        static fn(Source $source) => !in_array($source, Scope::Scope3->companyOnlySources(), true),
                    )),
                ),
            ],
        );
    }
}
