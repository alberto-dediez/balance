<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay;

use App\CarbonFootprint\Application\OvernightStay\Update\EmissionFactorData;
use App\CarbonFootprint\Application\OvernightStay\Update\UpdateOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Update\UpdateOvernightStayService;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateOvernightStayType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/overnight-stays/edit/{overnightStayId}', name: 'admin_overnight_stays_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetOvernightStayFromIdTrait;

    public function __invoke(
        string $overnightStayId,
        OvernightStayRepository $overnightStayRepository,
        UpdateOvernightStayService $updateOvernightStay,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $overnightStay = $this->getOvernightStayFromId($overnightStayId, $overnightStayRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $overnightStay);

        $emissionFactors = [];
        foreach ($overnightStay->emissionFactors() as $overnightStayEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($overnightStayEmissionFactor->emissionFactor());
        }

        $overnightStayRequest = new UpdateOvernightStayRequest(
            overnightStayId: $overnightStay->id(),
            name: $overnightStay->name(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateOvernightStayType::class, $overnightStayRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateOvernightStay, $loggedUser) {
                $overnightStayRequest = $form->getData();

                $updateOvernightStay($overnightStayRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Pernoctación actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_overnight_stays');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/overnight_stays/edit.html.twig', [
            'overnightStay' => $overnightStay,
            'form' => $form->createView(),
        ]);
    }
}
