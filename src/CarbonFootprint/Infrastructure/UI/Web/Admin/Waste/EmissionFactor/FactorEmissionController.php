<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste\EmissionFactor;

use App\CarbonFootprint\Application\Waste\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\Waste\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\Waste\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\Waste\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\Waste\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\Waste\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkWasteEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateWasteEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/wastes/emission-factors/{year}', name: 'admin_wastes_emission_factors', requirements: ['year' => '\d+'], defaults: ['year' => null])]
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
            foreach ($response->wastes as $waste) {
                $rows[] = EmissionFactorRow::fromWaste(
                    $waste->id(),
                    $waste->name(),
                    $waste->emissionFactorFor($year),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkWasteEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_wastes_emission_factors', ['year' => $year]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_wastes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateWasteEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year),
            ['action' => $this->generateUrl('admin_wastes_emission_factors_create_year', ['sourceYear' => $year])],
        );

        return $this->render('@carbon_footprint/admin/wastes/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'wastes' => $response->wastes,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
        ]);
    }
}
