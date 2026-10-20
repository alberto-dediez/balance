<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer;

use App\CarbonFootprint\Application\ElectricityMarketer\Update\EmissionFactorData;
use App\CarbonFootprint\Application\ElectricityMarketer\Update\UpdateElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Update\UpdateElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateElectricityMarketerType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-marketers/edit/{electricityMarketerId}', name: 'admin_electricity_marketers_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetElectricityMarketerFromIdTrait;

    public function __invoke(
        string $electricityMarketerId,
        ElectricityMarketerRepository $electricityMarketerRepository,
        UpdateElectricityMarketerService $updateElectricityMarketer,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $electricityMarketer = $this->getElectricityMarketerFromId($electricityMarketerId, $electricityMarketerRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $electricityMarketer);

        $emissionFactors = [];
        foreach ($electricityMarketer->emissionFactors() as $electricityMarketerEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($electricityMarketerEmissionFactor);
        }

        $electricityMarketerRequest = new UpdateElectricityMarketerRequest(
            electricityMarketerId: $electricityMarketer->id(),
            name: $electricityMarketer->name(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateElectricityMarketerType::class, $electricityMarketerRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateElectricityMarketer, $loggedUser) {
                $electricityMarketerRequest = $form->getData();

                $updateElectricityMarketer($electricityMarketerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Comercializadora eléctrica actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_electricity_marketers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/electricity_marketers/edit.html.twig', [
            'electricityMarketer' => $electricityMarketer,
            'form' => $form->createView(),
        ]);
    }
}
