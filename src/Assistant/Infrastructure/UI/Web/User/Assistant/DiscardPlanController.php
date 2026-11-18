<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanRequest;
use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanService;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/assistant/plans/{planId}/discard', name: 'assistant_plan_discard', methods: ['POST'])]
#[CsrfProtected('discard-plan')]
#[IsGranted('ROLE_ADMIN')]
class DiscardPlanController extends AbstractController
{
    public function __invoke(string $planId, AssistantAccess $access, DiscardActionPlanService $discardPlan, LoggedUser $loggedUser, TranslatorInterface $translator): Response
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $discardPlan(new DiscardActionPlanRequest(new ActionPlanId($planId)), $loggedUser());
        } catch (PlanNotExecutableException $exception) {
            return new Response($translator->trans($exception->getMessage()), Response::HTTP_UNPROCESSABLE_ENTITY, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return new Response(null, Response::HTTP_NO_CONTENT, ['Cache-Control' => 'no-store']);
    }
}
