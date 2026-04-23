<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyRequest;
use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/edit/{preventionPlanId}/validate', name: 'prevention_plans_validate')]
class ValidatePropertiesController extends AbstractController
{
    use GetPreventionPlanFromIdTrait;

    public function __invoke(
        string $preventionPlanId,
        PreventionPlanRepository $preventionPlanRepository,
        ValidatePropertyService $validateProperty,
    ): Response {
        $preventionPlan = $this->getPreventionPlanFromId($preventionPlanId, $preventionPlanRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $preventionPlan);

        $invalidProperties = [];

        foreach ($preventionPlan->propertyIds() as $propertyId) {
            $response = $validateProperty(new ValidatePropertyRequest($propertyId));

            if ($response->hasErrors) {
                $invalidProperties[] = [
                    'propertyName' => $response->propertyName,
                    'errors' => $response->errors,
                ];
            }
        }

        if ([] === $invalidProperties) {
            return $this->redirectToRoute('prevention_plans_actions', ['preventionPlanId' => $preventionPlanId]);
        }

        return $this->render('@food_loss/prevention-plan/validate.html.twig', [
            'preventionPlan' => $preventionPlan,
            'invalidProperties' => $invalidProperties,
        ]);
    }
}
