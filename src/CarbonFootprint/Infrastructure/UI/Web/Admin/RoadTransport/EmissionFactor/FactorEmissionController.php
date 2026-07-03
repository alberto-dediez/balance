<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport\EmissionFactor;

use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\NoDefaultEmissionFactorGroupException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkRoadTransportEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateRoadTransportEmissionFactorsYearType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup\GetEmissionFactorGroupFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(
    '/admin/road-transports/emission-factors/{emissionFactorGroup}/{year}',
    name: 'admin_road_transports_emission_factors',
    requirements: ['year' => '\d+'],
    defaults: ['emissionFactorGroup' => null, 'year' => null],
)]
class FactorEmissionController extends AbstractController
{
    use EditTrait;
    use GetEmissionFactorGroupFromIdTrait;

    public function __invoke(
        ?string $emissionFactorGroup,
        ?int $year,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        ListEmissionFactorsService $listEmissionFactors,
        UpdateEmissionFactorsService $updateEmissionFactors,
        TranslatorInterface $translator,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {

        $emissionFactorGroupId = null !== $emissionFactorGroup
            ? new EmissionFactorGroupId($emissionFactorGroup)
            : $emissionFactorGroupRepository->findDefault()?->id();

        try {
            $response = $listEmissionFactors(
                new ListEmissionFactorsRequest(emissionFactorGroupId: $emissionFactorGroupId ?? EmissionFactorGroupId::generate(), year: $year),
                $loggedUser(),
            );

            if (null === $emissionFactorGroupId) {
                throw new NoDefaultEmissionFactorGroupException();
            }

            $group = $this->getEmissionFactorGroupFromId((string) $emissionFactorGroupId, $emissionFactorGroupRepository);

            $year = $response->year;

            $rows = [];
            foreach ($response->roadTransports as $roadTransport) {
                $rows[] = EmissionFactorRow::fromRoadTransport(
                    $roadTransport->id(),
                    sprintf('%s - %s', $roadTransport->name(), $translator->trans($roadTransport->vehicle()->value, [], 'roadTransportVehicleType')),
                    $roadTransport->emissionFactorFor($year, $group->id()),
                    $group->id(),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkRoadTransportEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_road_transports_emission_factors', [
                    'emissionFactorGroup' => (string) $group->id(),
                    'year' => $year,
                ]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_road_transports');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateRoadTransportEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year, emissionFactorGroupId: (string) $group->id()),
            ['action' => $this->generateUrl('admin_road_transports_emission_factors_create_year', [
                'sourceYear' => $year,
                'emissionFactorGroup' => (string) $group->id(),
            ])],
        );

        $emissionFactorGroups = $emissionFactorGroupRepository->findByFilter(new EmissionFactorGroupFilter(limit: 0));

        return $this->render('@carbon_footprint/admin/road_transports/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'roadTransports' => $response->roadTransports,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
            'emissionFactorGroup' => $group,
            'emissionFactorGroups' => $emissionFactorGroups,
        ]);
    }
}
