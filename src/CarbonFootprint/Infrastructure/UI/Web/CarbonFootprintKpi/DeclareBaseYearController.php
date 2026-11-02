<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi;

use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearRequest;
use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearService;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearNotCompletedException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\DeclareBaseYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint-kpis/{subjectType}/{subjectId}/base-year', name: 'base_year_declarations_declare', requirements: ['subjectType' => 'property|company'], methods: ['POST'])]
class DeclareBaseYearController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $subjectId,
        DeclareBaseYearService $declareBaseYear,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $declareRequest = new DeclareBaseYearRequest($type, $subjectId);

        $submittedYear = $this->submittedYear($request);

        $form = $this->createForm(DeclareBaseYearType::class, $declareRequest, [
            'years' => null !== $submittedYear ? [$submittedYear] : [],
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            $this->addFlash('danger', count($errors) > 0 ? $errors->current()->getMessage() : new TranslatableMessage('No se ha podido declarar el año base.'));

            return $this->redirectToDashboard($type, $subjectId, $request);
        }

        try {
            $declareBaseYear($declareRequest, $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Año base declarado.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (BaseYearNotCompletedException) {
            $this->addFlash('danger', new TranslatableMessage('El año base debe tener una huella completada.'));
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToDashboard($type, $subjectId, $request);
    }

    private function submittedYear(Request $request): ?int
    {
        $data = $request->request->all('declare_base_year');
        $year = $data['year'] ?? null;

        return is_numeric($year) ? (int) $year : null;
    }

    private function redirectToDashboard(CarbonFootprintReportSubjectType $type, string $subjectId, Request $request): Response
    {
        $parameters = $request->query->has('year') ? ['year' => $request->query->getInt('year')] : [];

        return CarbonFootprintReportSubjectType::Company === $type
            ? $this->redirectToRoute('company_carbon_footprint_kpis', ['companyId' => $subjectId] + $parameters)
            : $this->redirectToRoute('property_carbon_footprint_kpis', ['propertyId' => $subjectId] + $parameters);
    }
}
