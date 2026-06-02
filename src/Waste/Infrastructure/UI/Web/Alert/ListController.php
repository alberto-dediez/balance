<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Alert;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\Alert\List\ListAlertsRequest;
use App\Waste\Application\Alert\List\ListAlertsService;
use App\Waste\Infrastructure\Symfony\Form\ListAlertsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alerts', name: 'alerts')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        ListAlertsService $listAlerts,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        [$filter, $form] = $this->filter($request, fn() => new ListAlertsRequest(
            accountId: $account?->id(),
        ), ListAlertsType::class);

        try {
            $response = $listAlerts($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/alerts/list.html.twig', [
            'alerts' => $response->alerts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
