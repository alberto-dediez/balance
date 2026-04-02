<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Application\CriticalPoint\Update\UpdateCriticalPointRequest;
use App\FoodLoss\Application\CriticalPoint\Update\UpdateCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateCriticalPointType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/critical-points/edit/{criticalPointId}', name: 'critical_points_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCriticalPointFromIdTrait;

    public function __invoke(
        string $criticalPointId,
        CriticalPointRepository $criticalPointRepository,
        UpdateCriticalPointService $updateCriticalPoint,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $criticalPoint = $this->getCriticalPointFromId($criticalPointId, $criticalPointRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $criticalPoint);

        $criticalPointRequest = new UpdateCriticalPointRequest(
            criticalPointId: $criticalPoint->id(),
            name: $criticalPoint->name(),
            propertyId: $criticalPoint->propertyId(),
            supplyChainStage: $criticalPoint->supplyChainStage(),
            serviceCategoryIds: $criticalPoint->serviceCategoryIds(),
        );

        $form = $this->createForm(UpdateCriticalPointType::class, $criticalPointRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCriticalPoint, $loggedUser) {
                $criticalPointRequest = $form->getData();

                $updateCriticalPoint($criticalPointRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Punto crítico actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('critical_points');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->render('@food_loss/critical-points/edit.html.twig', [
            'criticalPoint' => $criticalPoint,
            'form' => $form->createView(),
        ]);
    }
}
