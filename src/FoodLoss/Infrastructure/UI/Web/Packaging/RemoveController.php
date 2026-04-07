<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Application\Packaging\Remove\RemovePackagingRequest;
use App\FoodLoss\Application\Packaging\Remove\RemovePackagingService;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/packagings/remove/{packagingId}', name: 'packagings_remove')]
class RemoveController extends AbstractController
{
    use GetPackagingFromIdTrait;

    public function __invoke(
        string $packagingId,
        PackagingRepository $packagingRepository,
        RemovePackagingService $removePackaging,
        LoggedUser $loggedUser,
    ): Response {
        $packaging = $this->getPackagingFromId($packagingId, $packagingRepository);

        try {
            $removePackaging(new RemovePackagingRequest($packaging->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el envase {name}', ['{name}' => $packaging->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el envase {name}', ['{name}' => $packaging->name()]));
        }

        return $this->redirectToRoute('packagings');
    }
}
