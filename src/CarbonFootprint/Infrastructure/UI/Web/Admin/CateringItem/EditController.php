<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem;

use App\CarbonFootprint\Application\CateringItem\Update\EmissionFactorData;
use App\CarbonFootprint\Application\CateringItem\Update\UpdateCateringItemRequest;
use App\CarbonFootprint\Application\CateringItem\Update\UpdateCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateCateringItemType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/catering-items/edit/{cateringItemId}', name: 'admin_catering_items_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCateringItemFromIdTrait;

    public function __invoke(
        string $cateringItemId,
        CateringItemRepository $cateringItemRepository,
        UpdateCateringItemService $updateCateringItem,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $cateringItem = $this->getCateringItemFromId($cateringItemId, $cateringItemRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $cateringItem);

        $emissionFactors = [];
        foreach ($cateringItem->emissionFactors() as $cateringItemEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromCateringItemEmissionFactor($cateringItemEmissionFactor);
        }

        $cateringItemRequest = new UpdateCateringItemRequest(
            cateringItemId: $cateringItem->id(),
            name: $cateringItem->name(),
            category: $cateringItem->category(),
            unit: $cateringItem->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateCateringItemType::class, $cateringItemRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCateringItem, $loggedUser) {
                $cateringItemRequest = $form->getData();

                $updateCateringItem($cateringItemRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Elemento de catering actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_catering_items');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/catering_items/edit.html.twig', [
            'cateringItem' => $cateringItem,
            'form' => $form->createView(),
        ]);
    }
}
