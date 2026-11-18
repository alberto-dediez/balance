<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationMessageAdded;
use App\Assistant\Domain\Model\Conversation\ConversationMessageCompleted;
use App\Assistant\Domain\Model\Conversation\ConversationMessageFailed;
use App\Assistant\Domain\Model\Conversation\ConversationRemoved;
use App\Assistant\Domain\Model\Conversation\ConversationRenamed;
use App\Assistant\Domain\Model\Conversation\ConversationStarted;
use App\Assistant\Domain\Model\Conversation\InvalidConversationTitleException;
use App\Assistant\Domain\Model\Conversation\InvalidMessageException;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentDraft;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Assistant\Domain\Model\Conversation\ToolCallId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testStartsWithTheDefaultTitle(): void
    {
        $userId = UserId::generate();
        $conversation = ConversationMother::startedBy($userId);

        $this->assertSame('Nueva conversación', $conversation->title());
        $this->assertTrue($conversation->isOf($userId));
        $this->assertInstanceOf(ConversationStarted::class, $this->spy->lastEvent());
    }

    public function testTheFirstMessageGivesTheTitleInOneLine(): void
    {
        $conversation = ConversationMother::startedBy(UserId::generate());
        $conversation->addUserMessage(MessageId::generate(), "  Crea una tarea\n  en Acme  ", '/es/admin', 'Inicio', 'Europe/Madrid', new \DateTimeImmutable());

        $this->assertSame('Crea una tarea en Acme', $conversation->title());
        $this->assertSame("Crea una tarea\n  en Acme", $conversation->messages()[0]->text());
        $this->assertSame('Europe/Madrid', $conversation->lastUserMessage()?->timezone());
    }

    public function testTheTitleIsLimited(): void
    {
        $conversation = ConversationMother::withQuestion(UserId::generate(), str_repeat('a', 300));

        $this->assertSame(120, mb_strlen($conversation->title()));
    }

    public function testRejectsAnEmptyMessage(): void
    {
        $this->expectException(InvalidMessageException::class);

        ConversationMother::startedBy(UserId::generate())->addUserMessage(MessageId::generate(), '   ', '', '', 'UTC', new \DateTimeImmutable());
    }

    public function testRejectsATooLongMessage(): void
    {
        $this->expectException(InvalidMessageException::class);

        ConversationMother::startedBy(UserId::generate())->addUserMessage(MessageId::generate(), str_repeat('a', 4001), '', '', 'UTC', new \DateTimeImmutable());
    }

    public function testABusyConversationDoesNotAcceptMessages(): void
    {
        $conversation = ConversationMother::waitingForAnswer(UserId::generate());

        $this->assertTrue($conversation->isBusy());
        $this->expectException(ConversationBusyException::class);

        $conversation->addUserMessage(MessageId::generate(), 'Otra', '', '', 'UTC', new \DateTimeImmutable());
    }

    public function testTheEventsNeverCarryTheTexts(): void
    {
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer(UserId::generate(), $messageId);
        $conversation->completeMessage($messageId, 'Respuesta secreta', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());

        foreach ($this->spy->events() as $event) {
            $payload = (string) json_encode($event->payload());

            $this->assertStringNotContainsString('Acme', $payload);
            $this->assertStringNotContainsString('secreta', $payload);
        }

        $this->assertInstanceOf(ConversationMessageAdded::class, $this->spy->events()[1]);
    }

    public function testCompletesTheMessageWithItsToolCallsAndCost(): void
    {
        $messageId = MessageId::generate();
        $now = new \DateTimeImmutable('2026-11-11 10:05:00');
        $conversation = ConversationMother::waitingForAnswer(UserId::generate(), $messageId);
        $conversation->recordToolCall($messageId, ToolCallId::generate(), 'list_tasks', ['title' => ['DNS']], '2 tareas encontradas', false, 12);
        $conversation->completeMessage($messageId, 'Hay 2 tareas', MessageKind::Text, null, new AssistantUsage('m', 1, 2, 0, 0, '0.001', 5), $now);

        $message = $conversation->messageOf($messageId);
        $event = $this->spy->lastEvent();

        $this->assertNotNull($message);
        $this->assertSame(MessageStatus::Completed, $message->status());
        $this->assertSame('Hay 2 tareas', $message->text());
        $this->assertSame(['title' => ['DNS']], $message->toolCalls()[0]->argumentValues());
        $this->assertFalse($conversation->isBusy());
        $this->assertEquals($now, $conversation->lastActivityAt());
        $this->assertInstanceOf(ConversationMessageCompleted::class, $event);
        $this->assertSame(1, $event->toolCalls());
        $this->assertSame('0.001000', $event->costUsd());
    }

    public function testFailsTheMessage(): void
    {
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer(UserId::generate(), $messageId);
        $conversation->failMessage($messageId, 'Proveedor caído', AssistantUsage::none(), new \DateTimeImmutable());

        $this->assertSame(MessageStatus::Failed, $conversation->messageOf($messageId)?->status());
        $this->assertSame('Proveedor caído', $conversation->messageOf($messageId)->error());
        $this->assertInstanceOf(ConversationMessageFailed::class, $this->spy->lastEvent());
    }

    public function testAMessageCanOnlyBeCompletedOnce(): void
    {
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer(UserId::generate(), $messageId);
        $conversation->completeMessage($messageId, 'Hecho', MessageKind::Text, null, AssistantUsage::none(), new \DateTimeImmutable());

        $this->expectException(InvalidMessageException::class);

        $conversation->failMessage($messageId, 'Error', AssistantUsage::none(), new \DateTimeImmutable());
    }

    public function testAddsAttachmentsToTheUserMessageWithoutExposingThemInTheEvent(): void
    {
        $conversation = ConversationMother::startedBy(UserId::generate());
        $draft = new MessageAttachmentDraft(MessageAttachmentId::generate(), 'secreto.pdf', 'application/pdf', 1234, 'path/secreto.pdf');

        $conversation->addUserMessage(MessageId::generate(), 'Mira esto', '', '', 'UTC', new \DateTimeImmutable(), [$draft]);

        $message = $conversation->lastUserMessage();
        $event = $this->spy->events()[1];

        $this->assertNotNull($message);
        $this->assertCount(1, $message->attachments());
        $this->assertSame('secreto.pdf', $message->attachments()[0]->fileName());
        $this->assertSame($message->attachments()[0], $conversation->attachmentOf($draft->id));
        $this->assertSame(['path/secreto.pdf'], $conversation->attachmentPaths());
        $this->assertInstanceOf(ConversationMessageAdded::class, $event);
        $this->assertSame(1, $event->attachmentsCount());
        $this->assertSame(1, $event->payload()['attachmentsCount']);
        $this->assertStringNotContainsString('secreto', (string) json_encode($event->payload()));
    }

    public function testRejectsMoreThanFiveAttachments(): void
    {
        $conversation = ConversationMother::startedBy(UserId::generate());
        $drafts = array_map(static fn(int $index): MessageAttachmentDraft => new MessageAttachmentDraft(MessageAttachmentId::generate(), sprintf('f%d.pdf', $index), 'application/pdf', 10, sprintf('p%d', $index)), range(1, 6));

        $this->expectException(InvalidMessageException::class);

        $conversation->addUserMessage(MessageId::generate(), 'Mira esto', '', '', 'UTC', new \DateTimeImmutable(), $drafts);
    }

    public function testRenamesWithoutExposingTheTitle(): void
    {
        $userId = UserId::generate();
        $conversation = ConversationMother::answered($userId);

        $conversation->rename("  Mi   título\nnuevo ", new \DateTimeImmutable());

        $event = $this->spy->lastEvent();

        $this->assertSame('Mi título nuevo', $conversation->title());
        $this->assertInstanceOf(ConversationRenamed::class, $event);
        $this->assertStringNotContainsString('título', (string) json_encode($event->payload()));
    }

    public function testRejectsAnEmptyTitle(): void
    {
        $this->expectException(InvalidConversationTitleException::class);

        ConversationMother::answered(UserId::generate())->rename('   ', new \DateTimeImmutable());
    }

    public function testRejectsATooLongTitle(): void
    {
        $this->expectException(InvalidConversationTitleException::class);

        ConversationMother::answered(UserId::generate())->rename(str_repeat('a', 121), new \DateTimeImmutable());
    }

    public function testRemovesAFreeConversation(): void
    {
        $conversation = ConversationMother::answered(UserId::generate());

        $conversation->remove(new \DateTimeImmutable());

        $this->assertInstanceOf(ConversationRemoved::class, $this->spy->lastEvent());
    }

    public function testABusyConversationCannotBeRemoved(): void
    {
        $conversation = ConversationMother::waitingForAnswer(UserId::generate());

        $this->expectException(ConversationBusyException::class);

        $conversation->remove(new \DateTimeImmutable());
    }

    public function testTheToolCallResultIsCutToTwoThousandCharacters(): void
    {
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer(UserId::generate(), $messageId);

        $conversation->recordToolCall($messageId, ToolCallId::generate(), 'list_tasks', [], 'resumen', false, 5, str_repeat('x', 3000));
        $conversation->recordToolCall($messageId, ToolCallId::generate(), 'list_tasks', [], 'resumen', false, 5);

        $calls = $conversation->messageOf($messageId)?->toolCalls() ?? [];

        $this->assertSame(2000, mb_strlen((string) $calls[0]->result()));
        $this->assertNull($calls[1]->result());
    }
}
