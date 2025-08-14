<?php

namespace App\Waste\Infrastructure\UI\Web\DailyWaste;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\ListDailyWastesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\DailyWaste\List\ListDailyWastesRequest;
use App\Waste\Application\DailyWaste\List\ListDailyWastesService;

#[Route('/daily-wastes', name: 'daily_wastes')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        ListDailyWastesService $listDailyWastes, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();
        $user = $loggedUser();

        list($filter, $form) = $this->filter($request, function () use ($account) {
            return  new ListDailyWastesRequest(
                accountId: $account?->id()
            );
        }, ListDailyWastesType::class);

        try {
            $response = $listDailyWastes($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/daily-wastes/list.html.twig', [
            'dailyWastes' => $response->dailyWastes,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
