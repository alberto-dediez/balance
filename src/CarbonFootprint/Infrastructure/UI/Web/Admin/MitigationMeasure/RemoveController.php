<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\Remove\RemoveMitigationMeasureRequest;
use App\CarbonFootprint\Application\MitigationMeasure\Remove\RemoveMitigationMeasureService;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measures/remove/{mitigationMeasureId}', name: 'admin_mitigation_measures_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMitigationMeasureFromIdTrait;

    public function __invoke(
        string $mitigationMeasureId,
        MitigationMeasureRepository $mitigationMeasureRepository,
        RemoveMitigationMeasureService $removeMitigationMeasure,
        LoggedUser $loggedUser,
    ): Response {
        $mitigationMeasure = $this->getMitigationMeasureFromId($mitigationMeasureId, $mitigationMeasureRepository);

        try {
            $removeMitigationMeasure(new RemoveMitigationMeasureRequest($mitigationMeasure->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la medida {measure}', ['{measure}' => $mitigationMeasure->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la medida {measure}', ['{measure}' => $mitigationMeasure->name()]));
        }

        return $this->redirectToRoute('admin_mitigation_measures');
    }
}
