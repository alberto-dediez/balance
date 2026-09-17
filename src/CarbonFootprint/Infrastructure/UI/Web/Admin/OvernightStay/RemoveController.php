<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay;

use App\CarbonFootprint\Application\OvernightStay\Remove\RemoveOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Remove\RemoveOvernightStayService;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/overnight-stays/remove/{overnightStayId}', name: 'admin_overnight_stays_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetOvernightStayFromIdTrait;

    public function __invoke(
        string $overnightStayId,
        OvernightStayRepository $overnightStayRepository,
        RemoveOvernightStayService $removeOvernightStay,
        LoggedUser $loggedUser,
    ): Response {
        $overnightStay = $this->getOvernightStayFromId($overnightStayId, $overnightStayRepository);

        try {
            $removeOvernightStay(new RemoveOvernightStayRequest($overnightStay->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la pernoctación {name}', ['{name}' => $overnightStay->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la pernoctación {name}', ['{name}' => $overnightStay->name()]));
        }

        return $this->redirectToRoute('admin_overnight_stays');
    }
}
