<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode\ChangePropertyCarbonFootprintPrecalculatedModeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode\ChangePropertyCarbonFootprintPrecalculatedModeService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/property-carbon-footprints/{propertyCarbonFootprintId}/precalculated-mode/{precalculated}',
    name: 'property_carbon_footprints_precalculated_mode',
    requirements: ['precalculated' => '0|1'],
    methods: ['GET'],
)]
#[CsrfProtected('remove')]
class ChangePrecalculatedModeController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        string $precalculated,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        ChangePropertyCarbonFootprintPrecalculatedModeService $changePrecalculatedMode,
        LoggedUser $loggedUser,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if ($propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        $markAsPrecalculated = '1' === $precalculated;

        try {
            $changePrecalculatedMode(
                new ChangePropertyCarbonFootprintPrecalculatedModeRequest($propertyCarbonFootprint->id(), $markAsPrecalculated),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido cambiar el modo de introducción de datos: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

            return $this->redirectToRoute('property_carbon_footprints_scope_1', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        $this->addFlash('success', $markAsPrecalculated
            ? new TranslatableMessage('Huella marcada como ya calculada: introduce las emisiones de cada fuente directamente.')
            : new TranslatableMessage('La huella vuelve a calcularse por actividades.'));

        return $this->redirectToRoute('property_carbon_footprints_scope_1', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
    }
}
