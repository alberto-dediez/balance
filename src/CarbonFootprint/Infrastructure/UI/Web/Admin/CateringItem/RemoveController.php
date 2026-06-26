<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem;

use App\CarbonFootprint\Application\CateringItem\Remove\RemoveCateringItemRequest;
use App\CarbonFootprint\Application\CateringItem\Remove\RemoveCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/catering-items/remove/{cateringItemId}', name: 'admin_catering_items_remove')]
class RemoveController extends AbstractController
{
    use GetCateringItemFromIdTrait;

    public function __invoke(
        string $cateringItemId,
        CateringItemRepository $cateringItemRepository,
        RemoveCateringItemService $removeCateringItem,
        LoggedUser $loggedUser,
    ): Response {
        $cateringItem = $this->getCateringItemFromId($cateringItemId, $cateringItemRepository);

        try {
            $removeCateringItem(new RemoveCateringItemRequest($cateringItem->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el elemento de catering {cateringItem}', ['{cateringItem}' => $cateringItem->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el elemento de catering {cateringItem}', ['{cateringItem}' => $cateringItem->name()]));
        }

        return $this->redirectToRoute('admin_catering_items');
    }
}
