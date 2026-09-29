<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\Remove\RemoveMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Remove\RemoveMitigationMeasureCategoryService;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measure-categories/remove/{mitigationMeasureCategoryId}', name: 'admin_mitigation_measure_categories_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMitigationMeasureCategoryFromIdTrait;

    public function __invoke(
        string $mitigationMeasureCategoryId,
        MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        RemoveMitigationMeasureCategoryService $removeMitigationMeasureCategory,
        LoggedUser $loggedUser,
    ): Response {
        $mitigationMeasureCategory = $this->getMitigationMeasureCategoryFromId($mitigationMeasureCategoryId, $mitigationMeasureCategoryRepository);

        try {
            $removeMitigationMeasureCategory(new RemoveMitigationMeasureCategoryRequest($mitigationMeasureCategory->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la categoría de medidas {category}', ['{category}' => $mitigationMeasureCategory->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la categoría de medidas {category}', ['{category}' => $mitigationMeasureCategory->name()]));
        }

        return $this->redirectToRoute('admin_mitigation_measure_categories');
    }
}
