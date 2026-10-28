<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerProviders\AnswerCompanyCarbonFootprintProvidersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerProviders\AnswerCompanyCarbonFootprintProvidersService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/providers', name: 'company_carbon_footprints_providers', methods: ['GET', 'POST'])]
class ProvidersController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        AnswerCompanyCarbonFootprintProvidersService $answerProviders,
        ListOrganizationalProviderContributionsService $listContributions,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isScope3Answered()) {
            return $this->redirectToRoute(
                match (true) {
                    $companyCarbonFootprint->status()->isScope2Answered() => 'company_carbon_footprints_scope_3',
                    $companyCarbonFootprint->status()->isScope1Answered() => 'company_carbon_footprints_scope_2',
                    default => 'company_carbon_footprints_scope_1',
                },
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($companyCarbonFootprint->scope3Excluded()) {
            return $this->redirectToRoute(
                $companyCarbonFootprint->status()->isProvidersAnswered() ? 'company_carbon_footprints_mitigation_plan' : 'company_carbon_footprints_scope_3',
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('providers_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerProviders(new AnswerCompanyCarbonFootprintProvidersRequest($companyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('company_carbon_footprints_mitigation_plan', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        try {
            $contributions = $listContributions(
                new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Company, $companyCarbonFootprintId),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/company_carbon_footprints/step/providers.html.twig',
            [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'readOnly' => $companyCarbonFootprint->isCompleted(),
                'subjectType' => CarbonFootprintReportSubjectType::Company->value,
                'contributions' => $contributions->contributions,
                'pendingCount' => $contributions->pendingCount(),
            ],
        );
    }
}
