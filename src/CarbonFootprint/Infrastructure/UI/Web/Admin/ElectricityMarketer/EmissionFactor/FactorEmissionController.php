<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer\EmissionFactor;

use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkElectricityMarketerEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateElectricityMarketerEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-marketers/emission-factors/{year}', name: 'admin_electricity_marketers_emission_factors', requirements: ['year' => '\d+'], defaults: ['year' => null])]
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
            foreach ($response->electricityMarketers as $electricityMarketer) {
                $rows[] = EmissionFactorRow::fromElectricityMarketer(
                    $electricityMarketer->id(),
                    $electricityMarketer->name(),
                    $electricityMarketer->emissionFactorFor($year, EmissionFactorGroupId::default()),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkElectricityMarketerEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_electricity_marketers_emission_factors', ['year' => $year]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_electricity_marketers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateElectricityMarketerEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year),
            ['action' => $this->generateUrl('admin_electricity_marketers_emission_factors_create_year', ['sourceYear' => $year])],
        );

        return $this->render('@carbon_footprint/admin/electricity_marketers/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'electricityMarketers' => $response->electricityMarketers,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
        ]);
    }
}
