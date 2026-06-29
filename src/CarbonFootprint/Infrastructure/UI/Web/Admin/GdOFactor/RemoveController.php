<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\Remove\RemoveGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Remove\RemoveGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/gdo-factors/remove/{gdOFactorId}', name: 'admin_gdo_factors_remove')]
class RemoveController extends AbstractController
{
    use GetGdOFactorFromIdTrait;

    public function __invoke(
        string $gdOFactorId,
        GdOFactorRepository $gdOFactorRepository,
        RemoveGdOFactorService $removeGdOFactor,
        LoggedUser $loggedUser,
    ): Response {
        $gdOFactor = $this->getGdOFactorFromId($gdOFactorId, $gdOFactorRepository);

        try {
            $removeGdOFactor(new RemoveGdOFactorRequest($gdOFactor->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el factor GdO {name}', ['{name}' => $gdOFactor->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el factor GdO {name}', ['{name}' => $gdOFactor->name()]));
        }

        return $this->redirectToRoute('admin_gdo_factors');
    }
}
