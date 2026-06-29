<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay\EmissionFactor;

use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\OvernightStayEmissionFactorRow;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkOvernightStayEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateOvernightStayEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/overnight-stays/emission-factors/{year}', name: 'admin_overnight_stays_emission_factors', requirements: ['year' => '\d+'], defaults: ['year' => null])]
class FactorEmissionController extends AbstractController
{
    use EditTrait;

    public function __invoke(
        ?int $year,
        ListEmissionFactorsService $listEmissionFactors,
        UpdateEmissionFactorsService $updateEmissionFactors,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $response = $listEmissionFactors(new ListEmissionFactorsRequest(year: $year), $loggedUser());

            $year = $response->year;

            $rows = [];
            foreach ($response->overnightStays as $overnightStay) {
                $rows[] = OvernightStayEmissionFactorRow::fromOvernightStay(
                    $overnightStay->id(),
                    $overnightStay->name(),
                    $overnightStay->emissionFactorFor($year),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkOvernightStayEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_overnight_stays_emission_factors', ['year' => $year]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_overnight_stays');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateOvernightStayEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year),
            ['action' => $this->generateUrl('admin_overnight_stays_emission_factors_create_year', ['sourceYear' => $year])],
        );

        return $this->render('@carbon_footprint/admin/overnight_stays/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'overnightStays' => $response->overnightStays,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
        ]);
    }
}
