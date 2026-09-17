<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible;

use App\CarbonFootprint\Application\Combustible\Remove\RemoveCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Remove\RemoveCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/combustibles/remove/{combustibleId}', name: 'admin_combustibles_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetCombustibleFromIdTrait;

    public function __invoke(
        string $combustibleId,
        CombustibleRepository $combustibleRepository,
        RemoveCombustibleService $removeCombustible,
        LoggedUser $loggedUser,
    ): Response {
        $combustible = $this->getCombustibleFromId($combustibleId, $combustibleRepository);

        try {
            $removeCombustible(new RemoveCombustibleRequest($combustible->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el combustible {combustible}', ['{combustible}' => $combustible->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el combustible {combustible}', ['{combustible}' => $combustible->name()]));
        }

        return $this->redirectToRoute('admin_combustibles');
    }
}
