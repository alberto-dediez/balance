<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem\EmissionFactor;

use App\CarbonFootprint\Application\CateringItem\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkCateringItemEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateCateringItemEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/catering-items/emission-factors/{year}', name: 'admin_catering_items_emission_factors', requirements: ['year' => '\d+'], defaults: ['year' => null])]
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
            foreach ($response->cateringItems as $cateringItem) {
                $rows[] = EmissionFactorRow::fromCateringItem(
                    $cateringItem->id(),
                    $cateringItem->name(),
                    $cateringItem->emissionFactorFor($year),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkCateringItemEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_catering_items_emission_factors', ['year' => $year]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_catering_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateCateringItemEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year),
            ['action' => $this->generateUrl('admin_catering_items_emission_factors_create_year', ['sourceYear' => $year])],
        );

        return $this->render('@carbon_footprint/admin/catering_items/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'cateringItems' => $response->cateringItems,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
        ]);
    }
}
