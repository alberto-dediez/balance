<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector;

use App\CarbonFootprint\Application\EconomicSector\Update\EmissionFactorData;
use App\CarbonFootprint\Application\EconomicSector\Update\UpdateEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Update\UpdateEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateEconomicSectorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/economic-sectors/edit/{economicSectorId}', name: 'admin_economic_sectors_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetEconomicSectorFromIdTrait;

    public function __invoke(
        string $economicSectorId,
        EconomicSectorRepository $economicSectorRepository,
        UpdateEconomicSectorService $updateEconomicSector,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $economicSector = $this->getEconomicSectorFromId($economicSectorId, $economicSectorRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $economicSector);

        $emissionFactors = [];
        foreach ($economicSector->emissionFactors() as $economicSectorEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEconomicSectorEmissionFactor($economicSectorEmissionFactor);
        }

        $economicSectorRequest = new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: $economicSector->name(),
            unit: $economicSector->unit(),
            classificationCode: $economicSector->classificationCode(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateEconomicSectorType::class, $economicSectorRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEconomicSector, $loggedUser) {
                $economicSectorRequest = $form->getData();

                try {
                    $updateEconomicSector($economicSectorRequest, $loggedUser());
                } catch (EconomicSectorCodeAlreadyExistsException) {
                    $form->get('classificationCode')->addError(new FormError((string) new TranslatableMessage('Ya existe un sector económico con ese código')));

                    return;
                }

                $this->addFlash('success', new TranslatableMessage('Sector económico actualizado con éxito'));
            });

            if ($needsRedirect && $form->isValid()) {
                return $this->redirectToRoute('admin_economic_sectors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/economic_sectors/edit.html.twig', [
            'economicSector' => $economicSector,
            'form' => $form->createView(),
        ]);
    }
}
