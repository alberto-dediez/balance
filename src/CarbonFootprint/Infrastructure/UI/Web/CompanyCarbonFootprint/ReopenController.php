<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/reopen', name: 'company_carbon_footprints_reopen', methods: ['POST'])]
class ReopenController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        ReopenCompanyCarbonFootprintService $reopenCompanyCarbonFootprint,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$this->isCsrfTokenValid('reopen_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $reopenCompanyCarbonFootprint(
                new ReopenCompanyCarbonFootprintRequest($companyCarbonFootprint->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reabrir el cálculo: {error}', ['{error}' => $e->getMessage()]));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        $this->addFlash('success', new TranslatableMessage('El cálculo de la huella de carbono se ha reabierto y el informe generado se ha eliminado.'));

        return $this->redirectToRoute('company_carbon_footprints_scope_1', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
    }
}
