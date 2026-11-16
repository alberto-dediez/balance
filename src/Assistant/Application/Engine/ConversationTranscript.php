<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Assistant\Domain\Model\Conversation\MessageAttachment;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\ToolCall;
use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolResult;

class ConversationTranscript
{
    public const int RECENT_RESULTS = 3;

    public function __construct(
        private readonly ConversationAttachmentStorage $attachmentStorage,
        private readonly AssistantSettings $settings,
    ) {}

    /**
     * @param list<ActionPlan> $plans
     *
     * @return list<AiAgentMessage>
     */
    public function messagesFor(Conversation $conversation, array $plans, ToolContext $context): array
    {
        $plansById = [];

        foreach ($plans as $plan) {
            $plansById[(string) $plan->id()] = $plan;
        }

        $entries = $this->entries($conversation);
        $lastUserIndex = null;

        foreach ($entries as $index => $message) {
            if ($message->isUser()) {
                $lastUserIndex = $index;
            }
        }

        $included = $this->includedAttachmentMessages($entries);
        $withResult = $this->messagesWithResults($entries);
        $messages = [];

        foreach ($entries as $index => $message) {
            $blocks = $message->isUser()
                ? $this->userBlocks($message, $index === $lastUserIndex ? $context : null, isset($included[(string) $message->id()]))
                : [AiAgentBlock::text($this->assistantText($message, $plansById, isset($withResult[(string) $message->id()])))];

            $role = $message->isUser() ? AiAgentRole::User : AiAgentRole::Assistant;
            $last = array_key_last($messages);

            if (null !== $last && $messages[$last]->role === $role) {
                $messages[$last] = new AiAgentMessage($role, [...$messages[$last]->blocks, ...$blocks]);

                continue;
            }

            $messages[] = new AiAgentMessage($role, $blocks);
        }

        return $messages;
    }

    public function planText(ActionPlan $plan): string
    {
        $lines = [sprintf('Plan (%s): %s', $plan->status()->value, $plan->summary())];

        foreach ($plan->steps() as $step) {
            $line = sprintf('%d. %s [%s]', $step->position(), $step->label(), $step->status()->value);

            if (null !== $step->resultSummary()) {
                $line .= ' — ' . $step->resultSummary();
            }

            if (null !== $step->error()) {
                $line .= ' — Error: ' . $step->error();
            }

            $lines[] = $line;
        }

        if (null !== $plan->failureReason()) {
            $lines[] = 'Motivo del fallo: ' . $plan->failureReason();
        }

        return implode("\n", $lines);
    }

    /** @return list<Message> */
    private function entries(Conversation $conversation): array
    {
        $messages = $conversation->messages();
        $entries = [];

        foreach ($messages as $index => $message) {
            if ($message->isUser()) {
                $answer = $messages[$index + 1] ?? null;

                if (null !== $answer && $answer->isAssistant() && $answer->isFailed()) {
                    continue;
                }

                $entries[] = $message;

                continue;
            }

            if ($message->isCompleted()) {
                $entries[] = $message;
            }
        }

        return $entries;
    }

    public function includedAttachmentBytes(Conversation $conversation): int
    {
        return (int) array_sum($this->includedAttachmentMessages($this->entries($conversation)));
    }

    /**
     * @param list<Message> $entries
     *
     * @return array<string, int>
     */
    private function includedAttachmentMessages(array $entries): array
    {
        $included = [];
        $total = 0;

        foreach (array_reverse($entries) as $message) {
            if (!$message->isUser() || [] === $message->attachments()) {
                continue;
            }

            $size = array_sum(array_map(fn(MessageAttachment $attachment): int => $this->attachmentStorage->exists($attachment->path()) ? $attachment->size() : 0, $message->attachments()));
            $total += $size;

            if ($total > $this->settings->transcriptAttachmentsMaxBytes) {
                break;
            }

            $included[(string) $message->id()] = $size;
        }

        return $included;
    }

    /** @return list<AiAgentBlock> */
    private function userBlocks(Message $message, ?ToolContext $context, bool $attachmentsIncluded): array
    {
        $blocks = [];
        $warnings = [];

        foreach ($message->attachments() as $attachment) {
            $block = $attachmentsIncluded ? $this->attachmentBlock($attachment) : null;

            if (null === $block) {
                $warnings[] = sprintf('[Adjunto «%s» ya no está en el contexto]', $attachment->fileName());

                continue;
            }

            $blocks[] = $block;
        }

        $text = null === $context ? $message->text() : $this->contextLine($message, $context) . "\n" . $message->text();

        if ([] !== $warnings) {
            $text = implode("\n", $warnings) . "\n" . $text;
        }

        $blocks[] = AiAgentBlock::text($text);

        return $blocks;
    }

    private function attachmentBlock(MessageAttachment $attachment): ?AiAgentBlock
    {
        if (!$this->attachmentStorage->exists($attachment->path())) {
            return null;
        }

        $contents = $this->attachmentStorage->read($attachment->path());
        $file = new AiAttachment($attachment->fileName(), $attachment->mimeType(), $contents);

        if ($file->isPdf() || $file->isImage()) {
            return AiAgentBlock::attachment($file);
        }

        [$text, $truncated] = ToolResult::truncate(mb_scrub($contents, 'UTF-8'));

        return AiAgentBlock::text(sprintf("Fichero «%s»%s:\n%s", $attachment->fileName(), $truncated ? ' (truncado)' : '', $text));
    }

    /**
     * @param list<Message> $entries
     *
     * @return array<string, true>
     */
    private function messagesWithResults(array $entries): array
    {
        $recent = [];

        foreach (array_reverse($entries) as $message) {
            if (count($recent) >= self::RECENT_RESULTS) {
                break;
            }

            if ($message->isAssistant() && [] !== $message->toolCalls()) {
                $recent[(string) $message->id()] = true;
            }
        }

        return $recent;
    }

    /** @param array<string, ActionPlan> $plansById */
    private function assistantText(Message $message, array $plansById, bool $withResults): string
    {
        $plan = null === $message->planId() ? null : ($plansById[(string) $message->planId()] ?? null);
        $text = null === $plan || MessageKind::Text === $message->kind()
            ? $message->text()
            : trim($message->text() . "\n\n" . $this->planText($plan));

        if ([] === $message->toolCalls()) {
            return $text;
        }

        $lines = array_map(fn(ToolCall $call): string => $this->toolCallLine($call, $withResults), $message->toolCalls());

        return trim($text . "\n\n[Consultas realizadas en este turno:\n" . implode("\n", $lines) . "\n]");
    }

    private function toolCallLine(ToolCall $call, bool $withResult): string
    {
        $arguments = [];

        foreach ($call->argumentValues() as $name => $values) {
            $arguments[] = sprintf('%s=%s', $name, implode(',', $values));
        }

        $line = sprintf('- %s(%s) → %s', $call->tool(), implode(', ', $arguments), $call->summary());

        if ($withResult && null !== $call->result() && '' !== $call->result()) {
            $line .= "\n  " . $call->result();
        }

        return $line;
    }

    private function contextLine(Message $message, ToolContext $context): string
    {
        $line = sprintf('[Fecha y hora: %s (%s).', $context->now->setTimezone($context->timezone)->format('Y-m-d H:i'), $context->timezone->getName());

        if (null !== $message->screenTitle() || null !== $message->screenPath()) {
            $line .= sprintf(' Pantalla: «%s» — %s', $message->screenTitle() ?? '', $message->screenPath() ?? '');
        }

        return $line . ']';
    }
}
