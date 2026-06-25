<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem;

use App\CarbonFootprint\Application\MerchandisingItem\Remove\RemoveMerchandisingItemRequest;
use App\CarbonFootprint\Application\MerchandisingItem\Remove\RemoveMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/merchandising-items/remove/{merchandisingItemId}', name: 'admin_merchandising_items_remove')]
class RemoveController extends AbstractController
{
    use GetMerchandisingItemFromIdTrait;

    public function __invoke(
        string $merchandisingItemId,
        MerchandisingItemRepository $merchandisingItemRepository,
        RemoveMerchandisingItemService $removeMerchandisingItem,
        LoggedUser $loggedUser,
    ): Response {
        $merchandisingItem = $this->getMerchandisingItemFromId($merchandisingItemId, $merchandisingItemRepository);

        try {
            $removeMerchandisingItem(new RemoveMerchandisingItemRequest($merchandisingItem->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el artículo de merchandising {merchandisingItem}', ['{merchandisingItem}' => $merchandisingItem->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el artículo de merchandising {merchandisingItem}', ['{merchandisingItem}' => $merchandisingItem->name()]));
        }

        return $this->redirectToRoute('admin_merchandising_items');
    }
}
