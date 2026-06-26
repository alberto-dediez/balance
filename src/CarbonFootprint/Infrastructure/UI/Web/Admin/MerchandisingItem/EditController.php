<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem;

use App\CarbonFootprint\Application\MerchandisingItem\Update\EmissionFactorData;
use App\CarbonFootprint\Application\MerchandisingItem\Update\UpdateMerchandisingItemRequest;
use App\CarbonFootprint\Application\MerchandisingItem\Update\UpdateMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMerchandisingItemType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/merchandising-items/edit/{merchandisingItemId}', name: 'admin_merchandising_items_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMerchandisingItemFromIdTrait;

    public function __invoke(
        string $merchandisingItemId,
        MerchandisingItemRepository $merchandisingItemRepository,
        UpdateMerchandisingItemService $updateMerchandisingItem,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $merchandisingItem = $this->getMerchandisingItemFromId($merchandisingItemId, $merchandisingItemRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $merchandisingItem);

        $emissionFactors = [];
        foreach ($merchandisingItem->emissionFactors() as $merchandisingItemEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromMerchandisingItemEmissionFactor($merchandisingItemEmissionFactor);
        }

        $merchandisingItemRequest = new UpdateMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
            name: $merchandisingItem->name(),
            category: $merchandisingItem->category(),
            unit: $merchandisingItem->unit(),
            unitWeight: $merchandisingItem->unitWeight(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateMerchandisingItemType::class, $merchandisingItemRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMerchandisingItem, $loggedUser) {
                $merchandisingItemRequest = $form->getData();

                $updateMerchandisingItem($merchandisingItemRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Artículo de merchandising actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_merchandising_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/merchandising_items/edit.html.twig', [
            'merchandisingItem' => $merchandisingItem,
            'form' => $form->createView(),
        ]);
    }
}
