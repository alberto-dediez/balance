<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Application\Packaging\Update\UpdatePackagingRequest;
use App\FoodLoss\Application\Packaging\Update\UpdatePackagingService;
use App\FoodLoss\Domain\Model\Packaging\PackagingPropertyStockData;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdatePackagingType;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/packagings/edit/{packagingId}', name: 'packagings_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetPackagingFromIdTrait;

    public function __invoke(
        string $packagingId,
        PackagingRepository $packagingRepository,
        UpdatePackagingService $updatePackaging,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        PropertyRepository $propertyRepository,
        Request $request,
    ): Response {
        $packaging = $this->getPackagingFromId($packagingId, $packagingRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $packaging);

        $account = $loggedAccount();

        $properties = $propertyRepository->findByFilter(new PropertyFilter(
            accountId: $account?->id(),
            limit: 0,
        ));

        $existingStocks = $packaging->stocks();

        $stocks = array_map(function ($property) use ($existingStocks): PackagingPropertyStockData {
            $existing = $existingStocks->getByPropertyId($property->id());

            return new PackagingPropertyStockData(
                propertyId: $property->id(),
                stock: null !== $existing ? $existing->stock() : 0,
            );
        }, $properties);

        $packagingRequest = new UpdatePackagingRequest(
            packagingId: $packaging->id(),
            name: $packaging->name(),
            packagingType: $packaging->packagingType(),
            stocks: $stocks,
        );

        $form = $this->createForm(UpdatePackagingType::class, $packagingRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePackaging, $loggedUser): void {
                $packagingRequest = $form->getData();

                $updatePackaging($packagingRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Envase actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('packagings');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/packagings/edit.html.twig', [
            'packaging' => $packaging,
            'form' => $form->createView(),
        ]);
    }
}
