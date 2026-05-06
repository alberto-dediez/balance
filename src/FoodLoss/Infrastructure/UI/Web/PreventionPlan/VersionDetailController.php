<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/history/{preventionPlanId}/{versionNumber}', name: 'prevention_plans_version_detail')]
class VersionDetailController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        int $versionNumber,
        PreventionPlanRepository $preventionPlanRepository,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $preventionPlan);

        $version = $preventionPlan->versions()->findByVersionNumber($versionNumber);
        if (null === $version) {
            throw $this->createNotFoundException('Versión no encontrada');
        }

        return $this->render('@food_loss/prevention-plan/version-detail.html.twig', [
            'preventionPlan' => $preventionPlan,
            'version' => $version,
        ]);
    }
}
