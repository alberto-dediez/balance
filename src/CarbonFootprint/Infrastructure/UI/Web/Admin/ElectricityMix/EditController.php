<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Application\ElectricityMix\Update\EmissionFactorData;
use App\CarbonFootprint\Application\ElectricityMix\Update\UpdateElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Update\UpdateElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateElectricityMixType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-mixes/edit/{electricityMixId}', name: 'admin_electricity_mixes_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetElectricityMixFromIdTrait;

    public function __invoke(
        string $electricityMixId,
        ElectricityMixRepository $electricityMixRepository,
        UpdateElectricityMixService $updateElectricityMix,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $electricityMix = $this->getElectricityMixFromId($electricityMixId, $electricityMixRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $electricityMix);

        $emissionFactors = [];
        foreach ($electricityMix->emissionFactors() as $electricityMixEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($electricityMixEmissionFactor);
        }

        $electricityMixRequest = new UpdateElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
            countryId: $electricityMix->countryId(),
            emissionFactors: $emissionFactors,
        );

        $form = $this->createForm(UpdateElectricityMixType::class, $electricityMixRequest);

        try {
            $countryAlreadyUsed = false;

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateElectricityMix, $loggedUser, &$countryAlreadyUsed) {
                $electricityMixRequest = $form->getData();

                try {
                    $updateElectricityMix($electricityMixRequest, $loggedUser());
                } catch (ElectricityMixCountryAlreadyRegisteredException) {
                    $countryAlreadyUsed = true;
                    $this->addFlash('danger', new TranslatableMessage('Ya existe un mix eléctrico para ese país'));

                    return;
                }

                $this->addFlash('success', new TranslatableMessage('Mix eléctrico actualizado con éxito'));
            });

            if ($needsRedirect && !$countryAlreadyUsed) {
                return $this->redirectToRoute('admin_electricity_mixes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/electricity_mixes/edit.html.twig', [
            'electricityMix' => $electricityMix,
            'form' => $form->createView(),
        ]);
    }
}
