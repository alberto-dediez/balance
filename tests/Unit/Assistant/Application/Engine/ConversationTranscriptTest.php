<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentDraft;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\ToolCallId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use PHPUnit\Framework\TestCase;

class ConversationTranscriptTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testOnlyTheLastUserMessageCarriesTheContextLine(): void
    {
        $userId = UserId::generate();
        $conversation = ConversationMother::answered($userId);
        $conversation->addUserMessage(MessageId::generate(), 'Cierra esta tarea', '/es/admin/tasks/1/detail', 'Tareas > Migrar DNS', 'Europe/Madrid', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertCount(3, $messages);
        $this->assertSame(AiAgentRole::User, $messages[0]->role);
        $this->assertSame('¿Qué tareas tiene Acme?', $messages[0]->blocks[0]->text);
        $this->assertSame(AiAgentRole::Assistant, $messages[1]->role);
        $this->assertSame(
            "[Fecha y hora: 2026-11-11 10:42 (Europe/Madrid). Pantalla: «Tareas > Migrar DNS» — /es/admin/tasks/1/detail]\nCierra esta tarea",
            $messages[2]->blocks[0]->text,
        );
    }

    public function testFailedAnswersAreOmittedWithTheirQuestion(): void
    {
        $userId = UserId::generate();
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer($userId, $messageId);
        $conversation->failMessage($messageId, 'Error', AssistantUsage::none(), new \DateTimeImmutable());
        $conversation->addUserMessage(MessageId::generate(), 'Otra pregunta', '', '', 'UTC', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertCount(1, $messages);
        $this->assertStringEndsWith('Otra pregunta', $messages[0]->blocks[0]->text);
    }

    public function testSummarizesThePlanAndItsSteps(): void
    {
        $userId = UserId::generate();
        $planId = ActionPlanId::generate();
        $plan = ActionPlanMother::proposed($userId, id: $planId);
        $conversation = ConversationMother::withPlan($userId, $planId);
        $executionId = MessageId::generate();
        $conversation->addPendingAssistantMessage($executionId, MessageKind::Execution, new \DateTimeImmutable(), $planId);
        $conversation->completeMessage($executionId, 'Hecho', MessageKind::Execution, $planId, AssistantUsage::none(), new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [$plan], $this->context());

        $this->assertCount(2, $messages);
        $this->assertCount(2, $messages[1]->blocks);
        $this->assertStringContainsString('1. Registrar tarea [pending]', $messages[1]->blocks[0]->text);
        $this->assertStringContainsString('Plan (proposed)', $messages[1]->blocks[1]->text);
    }

    public function testThePlanAndItsExecutionFormOneAssistantTurnBetweenUserTurns(): void
    {
        $userId = UserId::generate();
        $planId = ActionPlanId::generate();
        $plan = ActionPlanMother::executing($userId, id: $planId);
        $plan->completeStep(1, 'Tarea registrada', ['taskId' => 'T'], null, null, [], new \DateTimeImmutable());
        $plan->completeStep(2, 'Acción añadida', [], null, null, [], new \DateTimeImmutable());
        $plan->finish(AssistantUsage::none(), new \DateTimeImmutable());
        $conversation = ConversationMother::withPlan($userId, $planId);
        $executionId = MessageId::generate();
        $conversation->addPendingAssistantMessage($executionId, MessageKind::Execution, new \DateTimeImmutable(), $planId);
        $conversation->completeMessage($executionId, 'He creado la tarea', MessageKind::Execution, $planId, AssistantUsage::none(), new \DateTimeImmutable());
        $conversation->addUserMessage(MessageId::generate(), '¿Y ahora qué?', '', '', 'Europe/Madrid', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [$plan], $this->context());

        $this->assertSame([AiAgentRole::User, AiAgentRole::Assistant, AiAgentRole::User], array_map(static fn($message) => $message->role, $messages));
        $this->assertCount(2, $messages[1]->blocks);
        $this->assertStringContainsString('Plan (executed)', $messages[1]->blocks[0]->text);
        $this->assertStringContainsString('2. Añadir acción [done] — Acción añadida', $messages[1]->blocks[1]->text);
        $this->assertStringStartsWith('He creado la tarea', $messages[1]->blocks[1]->text);
        $this->assertStringEndsWith('¿Y ahora qué?', $messages[2]->blocks[0]->text);
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 09:42:00', new \DateTimeZone('UTC')), new \DateTimeZone('Europe/Madrid'));
    }

    public function testAPdfAttachmentGoesBeforeTheTextOfItsMessage(): void
    {
        $conversation = $this->conversationWithAttachment('informe.pdf', 'application/pdf', 'PDFDATA');

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertCount(2, $messages[0]->blocks);
        $this->assertTrue($messages[0]->blocks[0]->isAttachment());
        $this->assertSame('PDFDATA', $messages[0]->blocks[0]->attachment?->contents);
        $this->assertStringEndsWith('Mira esto', $messages[0]->blocks[1]->text);
    }

    public function testATextAttachmentTravelsAsATextBlock(): void
    {
        $conversation = $this->conversationWithAttachment('notas.md', 'text/markdown', '# Notas');

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertTrue($messages[0]->blocks[0]->isText());
        $this->assertSame("Fichero «notas.md»:\n# Notas", $messages[0]->blocks[0]->text);
    }

    public function testAMissingFileLeavesAWarningBeforeTheText(): void
    {
        $conversation = $this->conversationWithAttachment('informe.pdf', 'application/pdf', 'PDFDATA');
        $this->attachmentStorage->reset();

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertCount(1, $messages[0]->blocks);
        $this->assertStringStartsWith('[Adjunto «informe.pdf» ya no está en el contexto]', $messages[0]->blocks[0]->text);
    }

    public function testTheAttachmentsOverTheBudgetAreReplacedByAWarningKeepingTheMostRecent(): void
    {
        $this->setUpAssistant(new AssistantSettings(transcriptAttachmentsMaxBytes: 20));
        $conversation = ConversationMother::startedBy(UserId::generate());

        foreach ([['viejo.pdf', 6], ['medio.pdf', 9], ['nuevo.pdf', 8]] as $index => [$name, $size]) {
            $conversation->addUserMessage(MessageId::generate(), 'Mensaje ' . $index, '', '', 'UTC', new \DateTimeImmutable(), [$this->draft($conversation, $name, $size)]);
            $answerId = MessageId::generate();
            $conversation->addPendingAssistantMessage($answerId, MessageKind::Text, new \DateTimeImmutable());
            $conversation->completeMessage($answerId, 'ok', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());
        }

        $conversation->addUserMessage(MessageId::generate(), 'Último', '', '', 'UTC', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertStringStartsWith('[Adjunto «viejo.pdf» ya no está en el contexto]', $messages[0]->blocks[0]->text);
        $this->assertTrue($messages[2]->blocks[0]->isAttachment());
        $this->assertTrue($messages[4]->blocks[0]->isAttachment());
    }

    public function testTheAttachmentsOfAMessageEnterOrLeaveTheBudgetTogether(): void
    {
        $this->setUpAssistant(new AssistantSettings(transcriptAttachmentsMaxBytes: 20));
        $conversation = ConversationMother::startedBy(UserId::generate());
        $conversation->addUserMessage(MessageId::generate(), 'Dos ficheros', '', '', 'UTC', new \DateTimeImmutable(), [$this->draft($conversation, 'uno.pdf', 6), $this->draft($conversation, 'dos.pdf', 6)]);
        $answerId = MessageId::generate();
        $conversation->addPendingAssistantMessage($answerId, MessageKind::Text, new \DateTimeImmutable());
        $conversation->completeMessage($answerId, 'ok', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());
        $conversation->addUserMessage(MessageId::generate(), 'Tres ficheros', '', '', 'UTC', new \DateTimeImmutable(), [$this->draft($conversation, 'a.pdf', 3), $this->draft($conversation, 'b.pdf', 3), $this->draft($conversation, 'c.pdf', 3)]);

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertCount(1, $messages[0]->blocks);
        $this->assertStringStartsWith("[Adjunto «uno.pdf» ya no está en el contexto]\n[Adjunto «dos.pdf» ya no está en el contexto]", $messages[0]->blocks[0]->text);
        $this->assertCount(4, $messages[2]->blocks);
        $this->assertTrue($messages[2]->blocks[2]->isAttachment());
    }

    public function testATruncatedTextAttachmentSaysSoInItsHeader(): void
    {
        $conversation = $this->conversationWithAttachment('largo.txt', 'text/plain', str_repeat('a', ToolResult::TEXT_MAX_LENGTH + 1));

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertStringStartsWith("Fichero «largo.txt» (truncado):\n", (string) $messages[0]->blocks[0]->text);
    }

    public function testAMissingFileDoesNotConsumeTheBudget(): void
    {
        $this->setUpAssistant(new AssistantSettings(transcriptAttachmentsMaxBytes: 10));
        $conversation = ConversationMother::startedBy(UserId::generate());
        $conversation->addUserMessage(MessageId::generate(), 'Antiguo', '', '', 'UTC', new \DateTimeImmutable(), [$this->draft($conversation, 'antiguo.pdf', 6)]);
        $answerId = MessageId::generate();
        $conversation->addPendingAssistantMessage($answerId, MessageKind::Text, new \DateTimeImmutable());
        $conversation->completeMessage($answerId, 'ok', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());
        $missing = $this->draft($conversation, 'borrado.pdf', 8);
        $conversation->addUserMessage(MessageId::generate(), 'Nuevo', '', '', 'UTC', new \DateTimeImmutable(), [$missing]);
        $this->attachmentStorage->remove($missing->path);

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertTrue($messages[0]->blocks[0]->isAttachment());
        $this->assertSame(6, $this->transcript()->includedAttachmentBytes($conversation));
    }

    public function testTheQueriesOfPreviousTurnsAreRememberedWithTheResultOnlyInTheThreeMostRecent(): void
    {
        $userId = UserId::generate();
        $conversation = ConversationMother::startedBy($userId);

        foreach (range(1, 4) as $turn) {
            $conversation->addUserMessage(MessageId::generate(), 'Pregunta ' . $turn, '', '', 'UTC', new \DateTimeImmutable());
            $answerId = MessageId::generate();
            $conversation->addPendingAssistantMessage($answerId, MessageKind::Text, new \DateTimeImmutable());
            $conversation->recordToolCall($answerId, ToolCallId::generate(), 'list_tasks', ['title' => ['DNS']], '2 tareas', false, 5, sprintf('{"turno":%d}', $turn));
            $conversation->completeMessage($answerId, 'Respuesta ' . $turn, MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());
        }

        $conversation->addUserMessage(MessageId::generate(), 'Última', '', '', 'UTC', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());
        $first = $messages[1]->blocks[0]->text;
        $second = $messages[3]->blocks[0]->text;

        $this->assertStringContainsString("[Consultas realizadas en este turno:\n- list_tasks(title=DNS) → 2 tareas\n]", $first);
        $this->assertStringNotContainsString('turno', str_replace('en este turno', '', $first));
        $this->assertStringContainsString('{"turno":2}', $second);
        $this->assertStringContainsString('{"turno":4}', $messages[7]->blocks[0]->text);
    }

    public function testAQueryStoredWithoutResultOnlyKeepsItsSummary(): void
    {
        $conversation = ConversationMother::startedBy(UserId::generate());
        $conversation->addUserMessage(MessageId::generate(), 'Pregunta', '', '', 'UTC', new \DateTimeImmutable());
        $answerId = MessageId::generate();
        $conversation->addPendingAssistantMessage($answerId, MessageKind::Text, new \DateTimeImmutable());
        $conversation->recordToolCall($answerId, ToolCallId::generate(), 'list_tasks', [], '2 tareas', false, 5);
        $conversation->completeMessage($answerId, 'Respuesta', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());
        $conversation->addUserMessage(MessageId::generate(), 'Otra', '', '', 'UTC', new \DateTimeImmutable());

        $messages = $this->transcript()->messagesFor($conversation, [], $this->context());

        $this->assertStringContainsString("- list_tasks() → 2 tareas\n]", $messages[1]->blocks[0]->text);
    }

    private function conversationWithAttachment(string $name, string $mimeType, string $contents): Conversation
    {
        $conversation = ConversationMother::startedBy(UserId::generate());
        $draft = $this->draft($conversation, $name, strlen($contents), $mimeType, $contents);
        $conversation->addUserMessage(MessageId::generate(), 'Mira esto', '', '', 'UTC', new \DateTimeImmutable(), [$draft]);

        return $conversation;
    }

    private function draft(Conversation $conversation, string $name, int $size, string $mimeType = 'application/pdf', ?string $contents = null): MessageAttachmentDraft
    {
        $id = MessageAttachmentId::generate();
        $path = sprintf('%s/%s', $conversation->id(), $id);
        $this->attachmentStorage->put($path, $contents ?? str_repeat('x', $size));

        return new MessageAttachmentDraft($id, $name, $mimeType, $size, $path);
    }
}
