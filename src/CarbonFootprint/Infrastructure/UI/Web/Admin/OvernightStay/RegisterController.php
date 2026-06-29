<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay;

use App\CarbonFootprint\Application\OvernightStay\Register\RegisterOvernightStayService;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterOvernightStayType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/overnight-stays/register', name: 'admin_overnight_stays_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterOvernightStayService $registerOvernightStay, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new OvernightStay(OvernightStayId::generate()));

        $form = $this->createForm(RegisterOvernightStayType::class);

        try {
            $overnightStayId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerOvernightStay, $loggedUser, &$overnightStayId) {
                $registerRequest = $form->getData();

                $response = $registerOvernightStay($registerRequest, $loggedUser());

                $overnightStayId = $response->overnightStayId;

                $this->addFlash('success', new TranslatableMessage('Pernoctación creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($overnightStayId instanceof OvernightStayId) {
                    return $this->redirectToRoute('admin_overnight_stays_edit', ['overnightStayId' => $overnightStayId]);
                }

                return $this->redirectToRoute('admin_overnight_stays');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/overnight_stays/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
