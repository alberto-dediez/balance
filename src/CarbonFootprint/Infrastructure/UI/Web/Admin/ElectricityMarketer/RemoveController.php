<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer;

use App\CarbonFootprint\Application\ElectricityMarketer\Remove\RemoveElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Remove\RemoveElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-marketers/remove/{electricityMarketerId}', name: 'admin_electricity_marketers_remove')]
class RemoveController extends AbstractController
{
    use GetElectricityMarketerFromIdTrait;

    public function __invoke(
        string $electricityMarketerId,
        ElectricityMarketerRepository $electricityMarketerRepository,
        RemoveElectricityMarketerService $removeElectricityMarketer,
        LoggedUser $loggedUser,
    ): Response {
        $electricityMarketer = $this->getElectricityMarketerFromId($electricityMarketerId, $electricityMarketerRepository);

        try {
            $removeElectricityMarketer(new RemoveElectricityMarketerRequest($electricityMarketer->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la comercializadora eléctrica {name}', ['{name}' => $electricityMarketer->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la comercializadora eléctrica {name}', ['{name}' => $electricityMarketer->name()]));
        }

        return $this->redirectToRoute('admin_electricity_marketers');
    }
}
