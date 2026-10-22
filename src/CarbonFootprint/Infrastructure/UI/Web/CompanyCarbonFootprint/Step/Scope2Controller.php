<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageExclusionHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\StepExclusionsView;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/scope-2', name: 'company_carbon_footprints_scope_2')]
class Scope2Controller extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        StageAnswerHttpMapper $answerMapper,
        StageExclusionHttpMapper $exclusionMapper,
        AnswerCompanyCarbonFootprintScopeService $answerScope,
        GetCompanyCarbonFootprintScopeService $getScope,
        ScopeStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isScope1Answered()) {
            return $this->redirectToRoute('company_carbon_footprints_scope_1', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST') && $companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('scope_2_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerScope(
                    new AnswerCompanyCarbonFootprintScopeRequest(
                        $companyCarbonFootprint->id(),
                        Scope::Scope2,
                        $this->answersByProperty($request, $answerMapper, Scope::Scope2),
                        $exclusionMapper->map($request, Scope::Scope2),
                    ),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('company_carbon_footprints_scope_2', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
            }

            return $this->redirectToRoute('company_carbon_footprints_scope_3', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        $response = $getScope(new GetCompanyCarbonFootprintScopeRequest($companyCarbonFootprint->id(), Scope::Scope2), $loggedUser());

        $blocks = [];
        foreach ($response->blocks as $block) {
            $blocks[] = $stepView->build($block->sections) + [
                'property' => $block->property,
                'prefixBase' => null === $block->property ? 'unassigned' : sprintf('properties[%s]', $block->property->id()),
            ];
        }

        return $this->render(
            '@carbon_footprint/company_carbon_footprints/step/scope-2.html.twig',
            [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'readOnly' => $companyCarbonFootprint->isCompleted(),
                'blocks' => $blocks,
                'exclusions' => StepExclusionsView::build($response->exclusions),
                'exclusionReasons' => ExclusionReason::cases(),
                'calculateUrl' => $this->generateUrl('company_carbon_footprints_emissions_calculate', ['companyCarbonFootprintId' => $companyCarbonFootprintId]),
            ],
        );
    }

    /**
     * @return list<PropertySectionAnswers>
     */
    private function answersByProperty(Request $request, StageAnswerHttpMapper $answerMapper, Scope $scope): array
    {
        $sections = $scope->sections();

        $blocks = [new PropertySectionAnswers(null, $answerMapper->map($request->request->all('unassigned'), $sections))];

        foreach ($request->request->all('properties') as $propertyId => $block) {
            if (!is_array($block)) {
                continue;
            }

            $blocks[] = new PropertySectionAnswers(new PropertyId((string) $propertyId), $answerMapper->map($block, $sections));
        }

        return $blocks;
    }
}
