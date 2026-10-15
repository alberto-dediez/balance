<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood;

use App\CarbonFootprint\Application\CapitalGood\Remove\RemoveCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Remove\RemoveCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/capital-goods/remove/{capitalGoodId}', name: 'admin_capital_goods_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetCapitalGoodFromIdTrait;

    public function __invoke(
        string $capitalGoodId,
        CapitalGoodRepository $capitalGoodRepository,
        RemoveCapitalGoodService $removeCapitalGood,
        LoggedUser $loggedUser,
    ): Response {
        $capitalGood = $this->getCapitalGoodFromId($capitalGoodId, $capitalGoodRepository);

        try {
            $removeCapitalGood(new RemoveCapitalGoodRequest($capitalGood->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el bien de capital {capitalGood}', ['{capitalGood}' => $capitalGood->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el bien de capital {capitalGood}', ['{capitalGood}' => $capitalGood->name()]));
        }

        return $this->redirectToRoute('admin_capital_goods');
    }
}
