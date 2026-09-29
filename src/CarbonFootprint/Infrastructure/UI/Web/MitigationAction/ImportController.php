<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsRequest;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsService;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/mitigation-actions/{subjectType}/{carbonFootprintId}/import', name: 'mitigation_actions_import', requirements: ['subjectType' => 'property|company'], methods: ['GET'])]
#[CsrfProtected('mitigation_actions_import')]
class ImportController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        ImportMitigationActionsService $importMitigationActions,
        MitigationPlanStepRoute $stepRoute,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $step = $stepRoute->resolve($type, $carbonFootprintId);

        try {
            $response = $importMitigationActions(new ImportMitigationActionsRequest($type, $carbonFootprintId), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se han importado {count} medidas.', ['{count}' => $response->imported]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MitigationPlanNotEditableException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        return $this->redirectToRoute($step['route'], $step['params']);
    }
}
