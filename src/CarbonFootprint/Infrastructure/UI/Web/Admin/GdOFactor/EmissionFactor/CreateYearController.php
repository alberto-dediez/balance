<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor\EmissionFactor;

use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateGdOFactorEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/gdo-factors/emission-factors/{sourceYear}/create-year', name: 'admin_gdo_factors_emission_factors_create_year', requirements: ['sourceYear' => '\d+'], methods: ['POST'])]
class CreateYearController extends AbstractController
{
    public function __invoke(
        int $sourceYear,
        CreateEmissionFactorsYearService $createEmissionFactorsYear,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $createRequest = new CreateEmissionFactorsYearRequest(sourceYear: $sourceYear);

        $form = $this->createForm(CreateGdOFactorEmissionFactorsYearType::class, $createRequest);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido crear el nuevo año.'));

            return $this->redirectToRoute('admin_gdo_factors_emission_factors', ['year' => $sourceYear]);
        }

        try {
            $response = $createEmissionFactorsYear($form->getData(), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Nuevo año creado con éxito'));

            return $this->redirectToRoute('admin_gdo_factors_emission_factors', ['year' => $response->targetYear]);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (EmissionFactorYearAlreadyExistsException) {
            $this->addFlash('danger', new TranslatableMessage('El año destino ya tiene factores de emisión.'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('El año destino no es válido.'));
        }

        return $this->redirectToRoute('admin_gdo_factors_emission_factors', ['year' => $sourceYear]);
    }
}
