<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode\ChangeCompanyCarbonFootprintPrecalculatedModeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode\ChangeCompanyCarbonFootprintPrecalculatedModeService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/company-carbon-footprints/{companyCarbonFootprintId}/precalculated-mode/{precalculated}',
    name: 'company_carbon_footprints_precalculated_mode',
    requirements: ['precalculated' => '0|1'],
    methods: ['GET'],
)]
#[CsrfProtected('remove')]
class ChangePrecalculatedModeController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        string $precalculated,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        ChangeCompanyCarbonFootprintPrecalculatedModeService $changePrecalculatedMode,
        LoggedUser $loggedUser,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if ($companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        $markAsPrecalculated = '1' === $precalculated;

        try {
            $changePrecalculatedMode(
                new ChangeCompanyCarbonFootprintPrecalculatedModeRequest($companyCarbonFootprint->id(), $markAsPrecalculated),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido cambiar el modo de introducción de datos: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

            return $this->redirectToRoute('company_carbon_footprints_scope_1', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        $this->addFlash('success', $markAsPrecalculated
            ? new TranslatableMessage('Huella marcada como ya calculada: introduce las emisiones de cada fuente directamente.')
            : new TranslatableMessage('La huella vuelve a calcularse por actividades.'));

        return $this->redirectToRoute('company_carbon_footprints_scope_1', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
    }
}
