<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\List\ListPreventionPlansRequest;
use App\FoodLoss\Application\PreventionPlan\List\ListPreventionPlansService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListPreventionPlansType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans', name: 'prevention_plans')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListPreventionPlansService $listPreventionPlans,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $account = $loggedAccount();

        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): ListPreventionPlansRequest {
                $filterRequest = new ListPreventionPlansRequest();
                $filterRequest->accountId = $loggedAccount()?->id();

                return $filterRequest;
            }, ListPreventionPlansType::class);

            $response = $listPreventionPlans($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/prevention-plan/list.html.twig', [
            'preventionPlans' => $response->preventionPlans,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
