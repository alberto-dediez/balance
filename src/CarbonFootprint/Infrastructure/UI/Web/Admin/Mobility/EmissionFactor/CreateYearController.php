<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility\EmissionFactor;

use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateMobilityEmissionFactorsYearType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/admin/mobilities/emission-factors/{emissionFactorGroup}/{sourceYear}/create-year',
    name: 'admin_mobilities_emission_factors_create_year',
    requirements: ['sourceYear' => '\d+'],
    methods: ['POST'],
)]
class CreateYearController extends AbstractController
{
    public function __invoke(
        string $emissionFactorGroup,
        int $sourceYear,
        CreateEmissionFactorsYearService $createEmissionFactorsYear,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $createRequest = new CreateEmissionFactorsYearRequest(sourceYear: $sourceYear, emissionFactorGroupId: $emissionFactorGroup);

        $form = $this->createForm(CreateMobilityEmissionFactorsYearType::class, $createRequest);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido crear el nuevo año.'));

            return $this->redirectToRoute('admin_mobilities_emission_factors', ['emissionFactorGroup' => $emissionFactorGroup, 'year' => $sourceYear]);
        }

        try {
            $response = $createEmissionFactorsYear($form->getData(), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Nuevo año creado con éxito'));

            return $this->redirectToRoute('admin_mobilities_emission_factors', ['emissionFactorGroup' => $emissionFactorGroup, 'year' => $response->targetYear]);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (EmissionFactorYearAlreadyExistsException) {
            $this->addFlash('danger', new TranslatableMessage('El año destino ya tiene factores de emisión.'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', new TranslatableMessage('El año destino no es válido.'));
        }

        return $this->redirectToRoute('admin_mobilities_emission_factors', ['emissionFactorGroup' => $emissionFactorGroup, 'year' => $sourceYear]);
    }
}
