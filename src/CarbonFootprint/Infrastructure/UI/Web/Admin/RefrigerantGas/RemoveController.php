<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas;

use App\CarbonFootprint\Application\RefrigerantGas\Remove\RemoveRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Remove\RemoveRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/refrigerant-gases/remove/{refrigerantGasId}', name: 'admin_refrigerant_gases_remove')]
class RemoveController extends AbstractController
{
    use GetRefrigerantGasFromIdTrait;

    public function __invoke(
        string $refrigerantGasId,
        RefrigerantGasRepository $refrigerantGasRepository,
        RemoveRefrigerantGasService $removeRefrigerantGas,
        LoggedUser $loggedUser,
    ): Response {
        $refrigerantGas = $this->getRefrigerantGasFromId($refrigerantGasId, $refrigerantGasRepository);

        try {
            $removeRefrigerantGas(new RemoveRefrigerantGasRequest($refrigerantGas->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el gas refrigerante {name}', ['{name}' => $refrigerantGas->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el gas refrigerante {name}', ['{name}' => $refrigerantGas->name()]));
        }

        return $this->redirectToRoute('admin_refrigerant_gases');
    }
}
