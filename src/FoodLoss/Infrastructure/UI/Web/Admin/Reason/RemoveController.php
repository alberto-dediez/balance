<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason;

use App\FoodLoss\Application\Reason\Remove\RemoveReasonRequest;
use App\FoodLoss\Application\Reason\Remove\RemoveReasonService;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/reasons/remove/{reasonId}', name: 'admin_reasons_remove')]
class RemoveController extends AbstractController
{
    use GetReasonFromIdTrait;

    public function __invoke(
        string $reasonId,
        ReasonRepository $reasonRepository,
        RemoveReasonService $removeReason,
        LoggedUser $loggedUser,
    ): Response {
        $reason = $this->getReasonFromId($reasonId, $reasonRepository);

        try {
            $removeReason(new RemoveReasonRequest($reason->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el motivo {reason}', ['{reason}' => $reason->description()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el motivo {reason}', ['{reason}' => $reason->description()]));
        }

        return $this->redirectToRoute('admin_reasons');
    }
}
