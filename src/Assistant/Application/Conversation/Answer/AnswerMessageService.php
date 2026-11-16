<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Answer;

use App\Assistant\Application\ActionPlan\Propose\ProposeActionPlanRequest;
use App\Assistant\Application\ActionPlan\Propose\ProposeActionPlanService;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Engine\AssistantPrompts;
use App\Assistant\Application\Engine\ConversationTranscript;
use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Application\Engine\ToolNotAllowedException;
use App\Assistant\Application\Engine\ToolOutcome;
use App\Assistant\Application\Engine\ToolRunner;
use App\Assistant\Application\Engine\ToolSchemaBuilder;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\InvalidPlanException;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Assistant\Domain\Model\Conversation\ToolCallId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentClient;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\Agent\AiToolDefinition;
use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiResponseException;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Live\LiveUpdates;
use Psr\Log\LoggerInterface;

class AnswerMessageService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public const string LIVE_KEY = 'assistant';
    public const string EMPTY_ANSWER = 'El asistente no ha devuelto respuesta';
    public const string TOO_MANY_ROUNDS = 'La consulta necesita demasiados pasos; concreta un poco más la petición';
    public const string DOCUMENT_TOO_LARGE_FOR_CONTEXT = 'Documento demasiado grande para el contexto';

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private AiAgentClient $client,
        private Toolbox $toolbox,
        private ToolRunner $runner,
        private ToolSchemaBuilder $schemaBuilder,
        private ConversationTranscript $transcript,
        private AssistantPrompts $prompts,
        private ProposeActionPlanService $proposePlan,
        private AssistantSettings $settings,
        private LiveUpdates $liveUpdates,
        private Clock $clock,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(AnswerMessageRequest $request, User $user): AnswerMessageResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::Edit, $user);

        $message = $conversation->messageOf($request->messageId);

        if (null === $message || !$message->isPending()) {
            return new AnswerMessageResponse($conversation->id(), $request->messageId, $message?->status());
        }

        $usage = AssistantUsage::none();

        try {
            $answered = $this->answer($conversation, $request->messageId, $user, $usage);
        } catch (\Throwable $exception) {
            if (!$exception instanceof \DomainException) {
                $this->logger->error('The assistant answer failed unexpectedly', ['conversationId' => (string) $conversation->id(), 'exception' => $exception]);
            }

            $this->fail($conversation, $request->messageId, $this->visibleError($exception), $usage);
            $answered = true;
        }

        if ($answered && $this->isStillPending($request->messageId)) {
            $this->conversations->add($conversation);
        }

        $this->liveUpdates->touchedForUser($user->id(), self::LIVE_KEY);

        return new AnswerMessageResponse($conversation->id(), $request->messageId, $conversation->messageOf($request->messageId)?->status());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AnswerMessageRequest;
    }

    private function answer(Conversation $conversation, MessageId $messageId, User $user, AssistantUsage &$usage): bool
    {
        $timezone = $this->timezone($conversation->lastUserMessage()?->timezone());
        $context = new ToolContext($this->clock->now(), $timezone, $this->settings->documentMaxBytes);
        $plans = $this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), limit: 0));
        $system = $this->prompts->querySystem($this->toolbox->writeToolsFor($user));
        $tools = [
            ...array_map(fn(AssistantTool $tool): AiToolDefinition => new AiToolDefinition($tool->name(), $tool->description(), $this->schemaBuilder->schemaOf($tool)), $this->toolbox->readToolsFor($user)),
            $this->prompts->proposePlanTool(),
        ];
        $messages = $this->transcript->messagesFor($conversation, $plans, $context);
        $attachmentBytes = $this->transcript->includedAttachmentBytes($conversation);

        for ($round = 0; $round < $this->settings->maxToolRounds; ++$round) {
            if (!$this->isStillPending($messageId)) {
                return false;
            }

            $step = $this->client->step(new AiAgentRequest(
                name: 'assistant-query',
                system: $system,
                tools: $tools,
                messages: $messages,
                modelTier: $this->settings->queryTier,
                effort: $this->settings->queryEffort,
            ));

            $usage = $usage->add($this->usageOf($step->usage));

            if (AiStopReason::EndTurn === $step->stopReason || (AiStopReason::ToolUse === $step->stopReason && [] === $step->toolCalls())) {
                $text = $step->text();

                if ('' === $text) {
                    $this->fail($conversation, $messageId, self::EMPTY_ANSWER, $usage);

                    return true;
                }

                $conversation->completeMessage($messageId, $text, MessageKind::Text, null, $usage, $this->clock->now());

                return true;
            }

            if (AiStopReason::ToolUse !== $step->stopReason) {
                $this->fail($conversation, $messageId, match ($step->stopReason) {
                    AiStopReason::MaxTokens => 'La respuesta es demasiado larga; concreta un poco más la petición',
                    AiStopReason::Refusal => 'El asistente no puede responder a esta petición',
                    default => 'El asistente no ha podido terminar la respuesta',
                }, $usage);

                return true;
            }

            $messages[] = $step->message;
            $results = [];

            foreach ($step->toolCalls() as $call) {
                if (AssistantPrompts::PROPOSE_PLAN === $call->name) {
                    [$planId, $summary, $result] = $this->propose($conversation, $call, $user, $timezone);

                    if (null !== $planId) {
                        $conversation->completeMessage($messageId, $summary, MessageKind::Plan, $planId, $usage, $this->clock->now());

                        return true;
                    }

                    $results[] = $result;

                    continue;
                }

                $results[] = $this->read($conversation, $messageId, $call, $user, $context, $attachmentBytes);
            }

            $messages[] = AiAgentMessage::toolResults($results);
        }

        $this->fail($conversation, $messageId, self::TOO_MANY_ROUNDS, $usage);

        return true;
    }

    /** @return array{0: ?ActionPlanId, 1: string, 2: AiAgentBlock} */
    private function propose(Conversation $conversation, AiToolCall $call, User $user, \DateTimeZone $timezone): array
    {
        $summary = is_string($call->input['summary'] ?? null) ? trim($call->input['summary']) : '';

        try {
            $response = ($this->proposePlan)(
                new ProposeActionPlanRequest($conversation->id(), $summary, $this->steps($call->input['steps'] ?? null), $timezone->getName()),
                $user,
            );

            return [$response->planId, $summary, AiAgentBlock::toolResult($call->id, 'Plan propuesto')];
        } catch (InvalidPlanException $exception) {
            return [null, '', AiAgentBlock::toolResult($call->id, "El plan no es válido; corrígelo y vuelve a llamar a propose_plan:\n- " . implode("\n- ", $exception->errors()), true)];
        }
    }

    private function read(Conversation $conversation, MessageId $messageId, AiToolCall $call, User $user, ToolContext $context, int &$attachmentBytes): AiAgentBlock
    {
        try {
            $outcome = $this->runner->run($call->name, $call->input, ToolMode::Read, $user, $context);
        } catch (ToolNotAllowedException $exception) {
            $outcome = ToolOutcome::failed($exception->getMessage());
        }

        if (null !== $outcome->result && [] !== $outcome->result->attachments) {
            $bytes = array_sum(array_map(static fn(AiAttachment $attachment): int => $attachment->size(), $outcome->result->attachments));

            if ($attachmentBytes + $bytes > $this->settings->transcriptAttachmentsMaxBytes) {
                $outcome = ToolOutcome::failed(self::DOCUMENT_TOO_LARGE_FOR_CONTEXT, $outcome->arguments, $outcome->durationMs);
            } else {
                $attachmentBytes += $bytes;
            }
        }

        $content = null === $outcome->result
            ? (string) $outcome->error
            : (string) json_encode($outcome->result->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        $conversation->recordToolCall(
            $messageId,
            ToolCallId::generate(),
            $call->name,
            null === $outcome->arguments ? $this->flatten($call->input) : $outcome->argumentValues(),
            $outcome->result->summary ?? (string) $outcome->error,
            !$outcome->isSuccessful(),
            $outcome->durationMs,
            $content,
        );

        if (null === $outcome->result) {
            return AiAgentBlock::toolResult($call->id, $content, true);
        }

        return AiAgentBlock::toolResult($call->id, $content, false, $outcome->result->attachments);
    }

    /** @return list<array{tool: string, arguments: list<array{name: string, value: string, fromStep: ?int}>}> */
    private function steps(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $steps = [];

        foreach ($raw as $step) {
            if (!is_array($step)) {
                continue;
            }

            $arguments = [];

            foreach (is_array($step['arguments'] ?? null) ? $step['arguments'] : [] as $argument) {
                if (!is_array($argument) || !is_string($argument['name'] ?? null)) {
                    continue;
                }

                $value = $argument['value'] ?? '';
                $fromStep = $argument['fromStep'] ?? null;

                $arguments[] = [
                    'name' => $argument['name'],
                    'value' => is_scalar($value) ? (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value) : '',
                    'fromStep' => is_int($fromStep) || (is_string($fromStep) && ctype_digit($fromStep)) ? (int) $fromStep : null,
                ];
            }

            $steps[] = [
                'tool' => is_string($step['tool'] ?? null) ? $step['tool'] : '',
                'arguments' => $arguments,
            ];
        }

        return $steps;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, list<string>>
     */
    private function flatten(array $input): array
    {
        $values = [];

        foreach ($input as $name => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                $values[(string) $name][] = is_scalar($item) ? (string) $item : (string) json_encode($item);
            }
        }

        return $values;
    }

    private function isStillPending(MessageId $messageId): bool
    {
        return MessageStatus::Pending === $this->conversations->messageStatusOf($messageId);
    }

    private function visibleError(\Throwable $exception): string
    {
        if (!$exception instanceof AiResponseException && !$exception instanceof \DomainException) {
            return ToolRunner::UNEXPECTED_ERROR;
        }

        return '' === trim($exception->getMessage()) ? ToolRunner::UNEXPECTED_ERROR : $exception->getMessage();
    }

    private function fail(Conversation $conversation, MessageId $messageId, string $error, AssistantUsage $usage): void
    {
        if (true === $conversation->messageOf($messageId)?->isPending()) {
            $conversation->failMessage($messageId, $error, $usage, $this->clock->now());
        }
    }

    private function usageOf(AiUsage $usage): AssistantUsage
    {
        return new AssistantUsage($usage->model, $usage->inputTokens, $usage->outputTokens, $usage->cacheReadTokens, $usage->cacheCreationTokens, AssistantUsage::formatCost($usage->costUsd), $usage->durationMs);
    }

    private function timezone(?string $timezone): \DateTimeZone
    {
        try {
            return new \DateTimeZone(null === $timezone || '' === trim($timezone) ? 'UTC' : $timezone);
        } catch (\Exception) {
            return new \DateTimeZone('UTC');
        }
    }
}
