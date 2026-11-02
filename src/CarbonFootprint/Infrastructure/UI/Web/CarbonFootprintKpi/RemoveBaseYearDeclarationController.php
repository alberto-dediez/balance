<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi;

use App\CarbonFootprint\Application\BaseYearDeclaration\Remove\RemoveBaseYearDeclarationRequest;
use App\CarbonFootprint\Application\BaseYearDeclaration\Remove\RemoveBaseYearDeclarationService;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint-kpis/base-year/{baseYearDeclarationId}/remove', name: 'base_year_declarations_remove')]
#[CsrfProtected('remove')]
class RemoveBaseYearDeclarationController extends AbstractController
{
    public function __invoke(
        string $baseYearDeclarationId,
        RemoveBaseYearDeclarationService $removeBaseYearDeclaration,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $declaration = $removeBaseYearDeclaration(new RemoveBaseYearDeclarationRequest(new BaseYearDeclarationId($baseYearDeclarationId)), $loggedUser())->declaration;
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        $this->addFlash('success', new TranslatableMessage('Declaración de año base eliminada.'));

        $parameters = $request->query->has('year') ? ['year' => $request->query->getInt('year')] : [];

        return CarbonFootprintReportSubjectType::Company === $declaration->subjectType()
            ? $this->redirectToRoute('company_carbon_footprint_kpis', ['companyId' => $declaration->subjectId()] + $parameters)
            : $this->redirectToRoute('property_carbon_footprint_kpis', ['propertyId' => $declaration->subjectId()] + $parameters);
    }
}
