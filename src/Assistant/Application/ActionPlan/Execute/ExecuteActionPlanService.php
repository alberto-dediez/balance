<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Execute;

use App\Assistant\Application\ActionPlan\ActionPlanAuthorizer;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Engine\AssistantPrompts;
use App\Assistant\Application\Engine\PlanStepVerifier;
use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Application\Engine\ToolNotAllowedException;
use App\Assistant\Application\Engine\ToolRunner;
use App\Assistant\Application\Engine\ToolSchemaBuilder;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanConcurrentlyModifiedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentClient;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\Agent\AiToolDefinition;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Application\Assistant\CurrentAssistantExecution;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Live\LiveUpdates;
use App\Shared\Domain\Event\DomainEventPublisher;
use Psr\Log\LoggerInterface;

class ExecuteActionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public const string LIVE_KEY = 'assistant';

    public function __construct(
        private ActionPlanRepository $plans,
        private ConversationRepository $conversations,
        private ActionPlanAuthorizer $authorizer,
        private AiAgentClient $client,
        private Toolbox $toolbox,
        private ToolRunner $runner,
        private ToolSchemaBuilder $schemaBuilder,
        private PlanStepVerifier $verifier,
        private AssistantPrompts $prompts,
        private CurrentAssistantExecution $execution,
        private AssistantSettings $settings,
        private LiveUpdates $liveUpdates,
        private Clock $clock,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ExecuteActionPlanRequest $request, User $user): ExecuteActionPlanResponse
    {
        $plan = $this->plans->find($request->planId);

        if (null === $plan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($plan, Action::Edit, $user);

        if (ActionPlanStatus::Approved !== $plan->status()) {
            return new ExecuteActionPlanResponse($plan->id(), $plan->status());
        }

        $plan->start($this->clock->now());

        try {
            $this->plans->add($plan);
        } catch (ActionPlanConcurrentlyModifiedException) {
            return new ExecuteActionPlanResponse($plan->id(), $plan->status());
        }

        $context = new ToolContext($this->clock->now(), $this->timezone($plan->timezone()), $this->settings->documentMaxBytes);
        $usage = AssistantUsage::none();
        $finalText = '';
        $aborted = false;
        $failed = false;
        $this->execution->begin((string) $plan->id());

        try {
            [$finalText, $aborted] = $this->run($plan, $user, $context, $usage);
        } catch (ActionPlanConcurrentlyModifiedException) {
            $aborted = true;
        } catch (\Throwable $exception) {
            $this->logger->error('The assistant plan execution failed unexpectedly', ['planId' => (string) $plan->id(), 'exception' => $exception]);
            $failed = true;
        } finally {
            $this->execution->end();
        }

        if (!$aborted) {
            $this->conclude($plan, $request->messageId, $usage, $finalText, $failed);
        }

        $this->liveUpdates->touchedForUser($user->id(), self::LIVE_KEY);

        return new ExecuteActionPlanResponse($plan->id(), $plan->status());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExecuteActionPlanRequest;
    }

    private function conclude(ActionPlan $plan, MessageId $messageId, AssistantUsage $usage, string $finalText, bool $failed): void
    {
        try {
            if (!$this->isStillExecuting($plan)) {
                return;
            }

            $now = $this->clock->now();

            if ($failed) {
                $plan->interrupt(ToolRunner::UNEXPECTED_ERROR, $now, $usage);
            } else {
                $plan->finish($usage, $now);
            }

            $this->plans->add($plan);

            $conversation = $this->conversations->find($plan->conversationId());
            $message = $conversation?->messageOf($messageId);

            if (null !== $conversation && null !== $message && $message->isPending()) {
                $text = ActionPlanStatus::Executed === $plan->status() && '' !== $finalText ? $finalText : $this->platformText($plan);
                $conversation->completeMessage($messageId, $text, MessageKind::Execution, $plan->id(), AssistantUsage::none(), $now);
                $this->conversations->add($conversation);
            }
        } catch (ActionPlanConcurrentlyModifiedException) {
            return;
        } catch (\Throwable $exception) {
            $this->logger->error('The assistant plan execution could not be concluded', ['planId' => (string) $plan->id(), 'exception' => $exception]);
        }
    }

    private function isStillExecuting(ActionPlan $plan): bool
    {
        return ActionPlanStatus::Executing === $plan->status() && ActionPlanStatus::Executing === $this->plans->statusOf($plan->id());
    }

    /** @return array{0: string, 1: bool} */
    private function run(ActionPlan $plan, User $user, ToolContext $context, AssistantUsage &$usage): array
    {
        $messages = [AiAgentMessage::userText($this->prompts->executionMessage($plan))];
        $tools = $this->tools($plan);
        $rounds = count($plan->steps()) + ActionPlan::MAX_REJECTED_CALLS + 1;

        for ($round = 0; $round < $rounds; ++$round) {
            if (!$this->isStillExecuting($plan)) {
                return ['', true];
            }

            try {
                $step = $this->client->step(new AiAgentRequest(
                    name: 'assistant-execution',
                    system: $this->prompts->executionSystem(),
                    tools: $tools,
                    messages: $messages,
                    modelTier: $this->settings->actionTier,
                    effort: $this->settings->actionEffort,
                    parallelToolCalls: false,
                ));
            } catch (\Throwable $exception) {
                $this->logger->error('The assistant execution model failed', ['planId' => (string) $plan->id(), 'exception' => $exception]);

                return ['', false];
            }

            $usage = $usage->add($this->usageOf($step->usage));
            $messages[] = $step->message;
            $calls = $step->toolCalls();

            if (AiStopReason::ToolUse !== $step->stopReason || [] === $calls) {
                return [$step->text(), false];
            }

            $results = [];
            $stop = false;

            foreach ($calls as $index => $call) {
                if ($index > 0) {
                    $results[] = AiAgentBlock::toolResult($call->id, 'Un paso por turno: esta llamada no se ha ejecutado.', true);

                    continue;
                }

                [$result, $stop] = $this->call($plan, $call, $user, $context);
                $results[] = $result;
            }

            $messages[] = AiAgentMessage::toolResults($results);

            if ($stop) {
                break;
            }
        }

        return ['', false];
    }

    /** @return array{0: AiAgentBlock, 1: bool} */
    private function call(ActionPlan $plan, AiToolCall $call, User $user, ToolContext $context): array
    {
        $pending = $plan->nextPendingStep();

        if (null === $pending) {
            $plan->rejectCall();

            return [AiAgentBlock::toolResult($call->id, 'El plan ya está completo: no hay más pasos que ejecutar.', true), $plan->hasTooManyRejectedCalls()];
        }

        try {
            $reason = $this->verifier->verify($pending, $plan, $call->name, $this->runner->argumentsOf($call->name, $call->input, $context));
        } catch (InvalidToolArgumentsException $exception) {
            $reason = implode(' ', $exception->errors());
        } catch (ToolNotAllowedException $exception) {
            $reason = $exception->getMessage();
        }

        if (null !== $reason) {
            $plan->rejectCall();

            return [AiAgentBlock::toolResult($call->id, 'Llamada rechazada: ' . $reason, true), $plan->hasTooManyRejectedCalls()];
        }

        DomainEventPublisher::instance()->lock();

        try {
            $outcome = $this->runner->run($call->name, $call->input, ToolMode::Write, $user, $context);
            $error = $outcome->error;
            $result = $outcome->result;
        } catch (ToolNotAllowedException $exception) {
            $error = $exception->getMessage();
            $result = null;
        } catch (\Throwable $exception) {
            DomainEventPublisher::instance()->clearPending();

            throw $exception;
        } finally {
            DomainEventPublisher::instance()->unlock();
        }

        if (null === $result) {
            $plan->failStep($pending->position(), $error ?? ToolRunner::UNEXPECTED_ERROR, $this->clock->now());
            $this->plans->add($plan);

            return [AiAgentBlock::toolResult($call->id, $error ?? ToolRunner::UNEXPECTED_ERROR, true), true];
        }

        $plan->completeStep(
            $pending->position(),
            $result->summary,
            $result->references,
            $result->link?->label,
            $result->link?->route,
            $result->link->parameters ?? [],
            $this->clock->now(),
        );
        $this->plans->add($plan);

        return [AiAgentBlock::toolResult($call->id, (string) json_encode(['summary' => $result->summary, 'references' => $result->references], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), false];
    }

    /** @return list<AiToolDefinition> */
    private function tools(ActionPlan $plan): array
    {
        $tools = [];

        foreach ($plan->steps() as $step) {
            $tool = $this->toolbox->get($step->tool());

            if (null !== $tool && ToolMode::Write === $tool->mode()) {
                $tools[$tool->name()] = new AiToolDefinition($tool->name(), $tool->description(), $this->schemaBuilder->schemaOf($tool));
            }
        }

        ksort($tools);

        return array_values($tools);
    }

    private function platformText(ActionPlan $plan): string
    {
        $total = count($plan->steps());
        $done = $plan->doneSteps();

        if (ActionPlanStatus::Executed === $plan->status()) {
            return sprintf('Se han ejecutado los %d pasos del plan.', $total);
        }

        $failed = $plan->failedStep();

        if (null !== $failed) {
            return sprintf('Se han ejecutado %d de %d pasos. El paso %d ha fallado: %s', $done, $total, $failed->position(), (string) $failed->error());
        }

        return sprintf('Se han ejecutado %d de %d pasos. %s', $done, $total, (string) $plan->failureReason());
    }

    private function usageOf(AiUsage $usage): AssistantUsage
    {
        return new AssistantUsage($usage->model, $usage->inputTokens, $usage->outputTokens, $usage->cacheReadTokens, $usage->cacheCreationTokens, AssistantUsage::formatCost($usage->costUsd), $usage->durationMs);
    }

    private function timezone(string $timezone): \DateTimeZone
    {
        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception) {
            return new \DateTimeZone('UTC');
        }
    }
}
