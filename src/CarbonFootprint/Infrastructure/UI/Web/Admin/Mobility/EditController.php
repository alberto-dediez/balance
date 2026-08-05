<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility;

use App\CarbonFootprint\Application\Mobility\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Mobility\Update\UpdateMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Update\UpdateMobilityService;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMobilityType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mobilities/edit/{mobilityId}', name: 'admin_mobilities_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMobilityFromIdTrait;

    public function __invoke(
        string $mobilityId,
        MobilityRepository $mobilityRepository,
        UpdateMobilityService $updateMobility,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $mobility = $this->getMobilityFromId($mobilityId, $mobilityRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $mobility);

        $emissionFactors = [];
        foreach ($mobility->emissionFactors() as $mobilityEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($mobilityEmissionFactor->emissionFactor());
        }

        $mobilityRequest = new UpdateMobilityRequest(
            mobilityId: $mobility->id(),
            name: $mobility->name(),
            unit: $mobility->unit(),
            travelMode: $mobility->travelMode(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateMobilityType::class, $mobilityRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMobility, $loggedUser) {
                $mobilityRequest = $form->getData();

                $updateMobility($mobilityRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Movilidad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_mobilities');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mobilities/edit.html.twig', [
            'mobility' => $mobility,
            'form' => $form->createView(),
        ]);
    }
}
