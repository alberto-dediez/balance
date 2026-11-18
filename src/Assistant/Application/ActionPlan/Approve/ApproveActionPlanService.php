<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Approve;

use App\Assistant\Application\ActionPlan\ActionPlanAuthorizer;
use App\Assistant\Application\ActionPlan\Execute\ExecuteActionPlanRequest;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ApproveActionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActionPlanRepository $plans,
        private ConversationRepository $conversations,
        private ActionPlanAuthorizer $authorizer,
        private AssistantSettings $settings,
        private DailyBudget $budget,
        private Clock $clock,
    ) {}

    public function __invoke(ApproveActionPlanRequest $request, User $user): ApproveActionPlanResponse
    {
        $plan = $this->plans->find($request->planId);

        if (null === $plan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($plan, Action::Edit, $user);

        if (!$this->settings->enabled) {
            throw new UnauthorizedActionException($plan, Action::Edit);
        }

        $this->budget->assertAvailable($user);

        $conversation = $this->conversations->find($plan->conversationId());

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $active = $this->plans->totalResultsByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: ActionPlanStatus::active(), limit: 0));

        if ($conversation->isBusy() || $active > 0) {
            throw new ConversationBusyException();
        }

        $now = $this->clock->now();
        $messageId = MessageId::generate();

        $plan->approve($user->id(), $now, $this->settings->planTtlMinutes);
        $conversation->addPendingAssistantMessage($messageId, MessageKind::Execution, $now, $plan->id());

        try {
            $this->plans->add($plan);
        } catch (ActionPlanConcurrentlyModifiedException) {
            throw PlanNotExecutableException::notProposed();
        }

        $this->conversations->add($conversation);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(new ExecuteActionPlanRequest($plan->id(), $messageId), Priority::HIGH),
            $user,
        );

        return new ApproveActionPlanResponse($plan->id(), $messageId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ApproveActionPlanRequest;
    }
}
