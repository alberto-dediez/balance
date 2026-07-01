<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem\EmissionFactor;

use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\BulkMerchandisingItemEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CreateMerchandisingItemEmissionFactorsYearType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup\GetEmissionFactorGroupFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/admin/merchandising-items/emission-factors/{emissionFactorGroup}/{year}',
    name: 'admin_merchandising_items_emission_factors',
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
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        // The group id is only resolved to a real EmissionFactorGroup entity *after* the authorization check
        // performed by ListEmissionFactorsService, so an unauthorized user gets a 403 rather than a 404 when
        // the requested (or default) group does not happen to exist.
        $emissionFactorGroupId = new EmissionFactorGroupId($emissionFactorGroup ?? (string) EmissionFactorGroupId::default());

        try {
            $response = $listEmissionFactors(
                new ListEmissionFactorsRequest(emissionFactorGroupId: $emissionFactorGroupId, year: $year),
                $loggedUser(),
            );

            $group = $this->getEmissionFactorGroupFromId((string) $emissionFactorGroupId, $emissionFactorGroupRepository);

            $year = $response->year;

            $rows = [];
            foreach ($response->merchandisingItems as $merchandisingItem) {
                $rows[] = EmissionFactorRow::fromMerchandisingItem(
                    $merchandisingItem->id(),
                    $merchandisingItem->name(),
                    $merchandisingItem->emissionFactorFor($year, $group->id()),
                    $group->id(),
                );
            }

            $updateRequest = new UpdateEmissionFactorsRequest(year: $year, rows: $rows);

            $form = $this->createForm(BulkMerchandisingItemEmissionFactorsType::class, $updateRequest, [
                'action' => $this->generateUrl('admin_merchandising_items_emission_factors', [
                    'emissionFactorGroup' => (string) $group->id(),
                    'year' => $year,
                ]),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactors, $loggedUser) {
                $updateEmissionFactors($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factores de emisión actualizados con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_merchandising_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $createYearForm = $this->createForm(
            CreateMerchandisingItemEmissionFactorsYearType::class,
            new CreateEmissionFactorsYearRequest(sourceYear: $year, emissionFactorGroupId: (string) $group->id()),
            ['action' => $this->generateUrl('admin_merchandising_items_emission_factors_create_year', [
                'sourceYear' => $year,
                'emissionFactorGroup' => (string) $group->id(),
            ])],
        );

        $emissionFactorGroups = $emissionFactorGroupRepository->findByFilter(new EmissionFactorGroupFilter(limit: 0));

        return $this->render('@carbon_footprint/admin/merchandising_items/emission_factors.html.twig', [
            'form' => $form->createView(),
            'createYearForm' => $createYearForm->createView(),
            'merchandisingItems' => $response->merchandisingItems,
            'year' => $year,
            'currentYear' => $response->currentYear,
            'canGoNext' => $response->canGoNext,
            'emissionFactorGroup' => $group,
            'emissionFactorGroups' => $emissionFactorGroups,
        ]);
    }
}
