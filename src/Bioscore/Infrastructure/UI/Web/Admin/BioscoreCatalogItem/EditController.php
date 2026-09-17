<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingRequest;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Infrastructure\Symfony\Form\UpdateBioscoreCatalogItemMappingType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/bioscore/catalog-items/edit/{itemId}', name: 'admin_bioscore_catalog_items_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetBioscoreCatalogItemFromIdTrait;

    public function __invoke(
        string $itemId,
        BioscoreCatalogItemRepository $repository,
        CatalogTargetFinder $targetFinder,
        UpdateBioscoreCatalogItemMappingService $updateMapping,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $item = $this->getBioscoreCatalogItemFromId($itemId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $item);

        $targetsById = [];

        foreach ($targetFinder->all($item->catalog()) as $target) {
            $targetsById[$target->id] = $target;
        }

        $mappingRequest = new UpdateBioscoreCatalogItemMappingRequest(
            itemId: $item->id(),
            targetId: $item->targetId(),
            ignore: $item->isIgnored(),
        );

        try {
            $form = $this->createForm(UpdateBioscoreCatalogItemMappingType::class, $mappingRequest, [
                'targets' => $targetsById,
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMapping, $loggedUser): void {
                $updateMapping($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Equivalencia de Bioscore actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute(BioscoreCatalogRoutes::routeName($item->catalog()));
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@bioscore/admin/catalogs/edit.html.twig', [
            'item' => $item,
            'target' => null !== $item->targetId() ? ($targetsById[$item->targetId()] ?? null) : null,
            'returnRoute' => BioscoreCatalogRoutes::routeName($item->catalog()),
            'form' => $form->createView(),
        ]);
    }
}
