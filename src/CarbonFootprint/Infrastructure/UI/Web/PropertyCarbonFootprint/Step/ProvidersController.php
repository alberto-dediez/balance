<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerProviders\AnswerPropertyCarbonFootprintProvidersRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerProviders\AnswerPropertyCarbonFootprintProvidersService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/providers', name: 'property_carbon_footprints_providers', methods: ['GET', 'POST'])]
class ProvidersController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        AnswerPropertyCarbonFootprintProvidersService $answerProviders,
        ListOrganizationalProviderContributionsService $listContributions,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isScope3Answered()) {
            return $this->redirectToRoute(
                match (true) {
                    $propertyCarbonFootprint->status()->isScope2Answered() => 'property_carbon_footprints_scope_3',
                    $propertyCarbonFootprint->status()->isScope1Answered() => 'property_carbon_footprints_scope_2',
                    default => 'property_carbon_footprints_scope_1',
                },
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($propertyCarbonFootprint->scope3Excluded()) {
            return $this->redirectToRoute(
                $propertyCarbonFootprint->status()->isProvidersAnswered() ? 'property_carbon_footprints_mitigation_plan' : 'property_carbon_footprints_scope_3',
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('providers_' . $propertyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerProviders(new AnswerPropertyCarbonFootprintProvidersRequest($propertyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('property_carbon_footprints_mitigation_plan', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        try {
            $contributions = $listContributions(
                new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, $propertyCarbonFootprintId),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/property_carbon_footprints/step/providers.html.twig',
            [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'readOnly' => $propertyCarbonFootprint->isCompleted(),
                'subjectType' => CarbonFootprintReportSubjectType::Property->value,
                'contributions' => $contributions->contributions,
                'pendingCount' => $contributions->pendingCount(),
            ],
        );
    }
}
