<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\Register\RegisterPreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Register\RegisterPreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterPreventionPlanType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/prevention-plans/register', name: 'prevention_plans_register')]
class RegisterController extends AbstractController
{
    public function __invoke(
        RegisterPreventionPlanService $registerPreventionPlan,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(
            SecurityAction::Register->value,
            new PreventionPlan(id: PreventionPlanId::generate(), accountId: $account?->id()),
        );

        assert($account instanceof Account);

        $form = $this->createForm(RegisterPreventionPlanType::class, new RegisterPreventionPlanRequest());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    /** @var RegisterPreventionPlanRequest $registerRequest */
                    $registerRequest = $form->getData();

                    $registerRequest->accountId = $account->id();

                    $response = $registerPreventionPlan($registerRequest, $user);

                    $this->addFlash('success', new TranslatableMessage('Plan de prevención creado correctamente: {name}', ['{name}' => $response->name]));

                    return $this->redirectToRoute('prevention_plans_validate', ['preventionPlanId' => (string) $response->preventionPlanId]);
                } catch (\Exception) {
                    $this->addFlash('danger', new TranslatableMessage('Error inesperado'));
                }
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/prevention-plan/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
