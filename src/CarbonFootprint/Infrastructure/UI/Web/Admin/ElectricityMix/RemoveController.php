<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\Remove\RemoveElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Remove\RemoveElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-mixes/remove/{electricityMixId}', name: 'admin_electricity_mixes_remove')]
class RemoveController extends AbstractController
{
    use GetElectricityMixFromIdTrait;

    public function __invoke(
        string $electricityMixId,
        ElectricityMixRepository $electricityMixRepository,
        RemoveElectricityMixService $removeElectricityMix,
        CountryRepository $countryRepository,
        LoggedUser $loggedUser,
    ): Response {
        $electricityMix = $this->getElectricityMixFromId($electricityMixId, $electricityMixRepository);

        $countryName = (string) $countryRepository->find($electricityMix->countryId())?->name();

        try {
            $removeElectricityMix(new RemoveElectricityMixRequest($electricityMix->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el mix eléctrico {name}', ['{name}' => $countryName]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el mix eléctrico {name}', ['{name}' => $countryName]));
        }

        return $this->redirectToRoute('admin_electricity_mixes');
    }
}
