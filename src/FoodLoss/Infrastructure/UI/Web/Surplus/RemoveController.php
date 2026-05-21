<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Remove\RemoveSurplusRequest;
use App\FoodLoss\Application\Surplus\Remove\RemoveSurplusService;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/surpluses/remove/{surplusId}', name: 'surpluses_remove')]
class RemoveController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        RemoveSurplusService $removeSurplus,
        LoggedUser $loggedUser,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        try {
            $removeSurplus(new RemoveSurplusRequest($surplus->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el excedente {surplus}', ['{surplus}' => (string) $surplus]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el excedente {surplus}', ['{surplus}' => (string) $surplus]));
        }

        return $this->redirectToRoute('surpluses');
    }
}
