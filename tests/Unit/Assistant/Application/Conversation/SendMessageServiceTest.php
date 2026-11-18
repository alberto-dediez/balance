<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\Answer\AnswerMessageRequest;
use App\Assistant\Application\Conversation\SendMessage\MessageAttachmentUpload;
use App\Assistant\Application\Conversation\SendMessage\SendMessageRequest;
use App\Assistant\Application\Engine\DailyLimitReachedException;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\InvalidMessageException;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\Assistant\ToolDocument;
use App\Shared\Application\CommandBus\Priority;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SendMessageServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testStartsAConversationAndQueuesTheAnswer(): void
    {
        $admin = UserMother::adminUser();

        $response = $this->sendService()(new SendMessageRequest(text: '¿Qué tareas tiene Acme?', screenPath: '/es/admin/tasks', screenTitle: 'Tareas', timezone: 'Europe/Madrid'), $admin);

        $conversation = $this->conversations->find($response->conversationId);
        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->isOf($admin->id()));
        $this->assertSame('¿Qué tareas tiene Acme?', $conversation->title());
        $this->assertCount(2, $conversation->messages());
        $this->assertSame(MessageStatus::Pending, $conversation->messageOf($response->assistantMessageId)?->status());
        $this->assertSame('/es/admin/tasks', $conversation->lastUserMessage()?->screenPath());

        $commands = $this->deferred->commands();
        $this->assertCount(1, $commands);
        $this->assertSame(Priority::HIGH, $commands[0]->priority());
        $command = $commands[0]->command();
        $this->assertInstanceOf(AnswerMessageRequest::class, $command);
        $this->assertTrue($command->messageId->equals($response->assistantMessageId));
    }

    public function testANewMessageDiscardsTheProposedPlan(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);
        $plan = ActionPlanMother::proposed($admin->id(), $conversation->id());
        $this->plans->add($plan);

        $this->sendService()(new SendMessageRequest($conversation->id(), 'sí, ejecútalo'), $admin);

        $this->assertSame(ActionPlanStatus::Discarded, $plan->status());
        $this->assertSame(DiscardReason::Superseded, $plan->discardReason());
    }

    public function testAnInvalidMessageKeepsTheProposedPlan(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);
        $plan = ActionPlanMother::proposed($admin->id(), $conversation->id());
        $this->plans->add($plan);

        try {
            $this->sendService()(new SendMessageRequest($conversation->id(), str_repeat('a', 4001)), $admin);
            $this->fail('The message must be invalid');
        } catch (InvalidMessageException) {
        }

        $this->assertSame(ActionPlanStatus::Proposed, $plan->status());
    }

    public function testABusyConversationRejectsTheMessage(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::waitingForAnswer($admin->id());
        $this->conversations->add($conversation);

        $this->expectException(ConversationBusyException::class);

        $this->sendService()(new SendMessageRequest($conversation->id(), 'Otra'), $admin);
    }

    public function testAConversationWithAPlanInExecutionIsBusy(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::executing($admin->id(), $conversation->id()));

        $this->expectException(ConversationBusyException::class);

        $this->sendService()(new SendMessageRequest($conversation->id(), 'Otra'), $admin);
    }

    public function testRejectsWhenTheDailyLimitIsReached(): void
    {
        $admin = UserMother::adminUser();
        $this->conversations->add(ConversationMother::answered($admin->id(), cost: '5.000000', now: new \DateTimeImmutable('2026-11-11 09:00:00')));

        $this->expectException(DailyLimitReachedException::class);

        $this->sendService()(new SendMessageRequest(text: 'Hola'), $admin);
    }

    public function testTheConversationOfAnotherAdminIsDenied(): void
    {
        $conversation = ConversationMother::answered(UserMother::superAdminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        $this->sendService()(new SendMessageRequest($conversation->id(), 'Hola'), UserMother::adminUser());
    }

    public function testOwnersCannotUseTheAssistant(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->sendService()(new SendMessageRequest(text: 'Hola'), UserMother::ownerUser());
    }

    public function testTheDisabledAssistantRejectsTheMessage(): void
    {
        $this->setUpAssistant(new AssistantSettings(enabled: false));

        try {
            $this->sendService()(new SendMessageRequest(text: 'Hola'), UserMother::adminUser());
            $this->fail('A disabled assistant must not accept messages');
        } catch (UnauthorizedActionException) {
            $this->assertSame(0, $this->conversations->totalResultsByFilter(new ConversationFilter(limit: 0)));
        }
    }

    public function testStoresTheAttachmentsOfTheMessage(): void
    {
        $admin = UserMother::adminUser();
        $path = $this->temporaryFile('contenido');

        $response = $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: [new MessageAttachmentUpload($path, 'informe.pdf', 'application/pdf', 9, 'pdf')]), $admin);

        $attachments = $this->conversations->find($response->conversationId)?->lastUserMessage()?->attachments() ?? [];

        $this->assertCount(1, $attachments);
        $this->assertSame('informe.pdf', $attachments[0]->fileName());
        $this->assertSame('contenido', $this->attachmentStorage->read($attachments[0]->path()));
    }

    public function testRejectsAnUnreadableAttachmentWithoutStoringAnything(): void
    {
        $admin = UserMother::adminUser();
        $path = $this->temporaryFile('contenido');

        try {
            $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: [new MessageAttachmentUpload($path, 'hoja.xlsx', 'application/vnd.ms-excel', 9, 'xlsx')]), $admin);
            $this->fail('The attachment must be rejected');
        } catch (InvalidMessageException) {
        }

        $this->assertSame(0, $this->conversations->totalResultsByFilter(new ConversationFilter(limit: 0)));
        $this->assertSame([], $this->deferred->commands());
    }

    public function testRejectsAnAttachmentOverTheSizeLimit(): void
    {
        $this->setUpAssistant(new AssistantSettings(documentMaxBytes: 5));
        $path = $this->temporaryFile('contenido');

        $this->expectException(InvalidMessageException::class);

        $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: [new MessageAttachmentUpload($path, 'informe.pdf', 'application/pdf', 9, 'pdf')]), UserMother::adminUser());
    }

    public function testRejectsAnEmptyAttachmentAsEmpty(): void
    {
        $path = $this->temporaryFile('');

        $this->expectExceptionObject(InvalidMessageException::emptyAttachment('vacio.txt'));

        $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: [new MessageAttachmentUpload($path, 'vacio.txt', 'text/plain', 0, 'txt')]), UserMother::adminUser());
    }

    public function testRejectsAnImageOverTheImageSizeLimit(): void
    {
        $path = $this->temporaryFile('imagen');

        $this->expectExceptionObject(InvalidMessageException::imageTooLarge('foto.png', ToolDocument::IMAGE_MAX_BYTES));

        $this->sendService()(new SendMessageRequest(text: 'Mira esto', attachments: [new MessageAttachmentUpload($path, 'foto.png', 'image/png', ToolDocument::IMAGE_MAX_BYTES + 1, 'png')]), UserMother::adminUser());
    }

    public function testRejectsATextAttachmentThatIsNotUtf8(): void
    {
        $contents = (string) mb_convert_encoding("Año;Importe\nEspaña;10", 'ISO-8859-1', 'UTF-8');
        $path = $this->temporaryFile($contents);

        try {
            $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: [new MessageAttachmentUpload($path, 'datos.csv', 'text/csv', strlen($contents), 'csv')]), UserMother::adminUser());
            $this->fail('The attachment must be rejected');
        } catch (InvalidMessageException $exception) {
            $this->assertSame(InvalidMessageException::notUtf8Attachment('datos.csv')->getMessage(), $exception->getMessage());
        }

        $this->assertSame(0, $this->conversations->totalResultsByFilter(new ConversationFilter(limit: 0)));
    }

    public function testRejectsAttachmentsWhoseTotalExceedsTheTranscriptBudget(): void
    {
        $this->setUpAssistant(new AssistantSettings(transcriptAttachmentsMaxBytes: 15));
        $path = $this->temporaryFile('contenido');
        $uploads = array_fill(0, 2, new MessageAttachmentUpload($path, 'informe.pdf', 'application/pdf', 9, 'pdf'));

        $this->expectExceptionObject(InvalidMessageException::attachmentsTooLarge());

        $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: $uploads), UserMother::adminUser());
    }

    public function testRejectsMoreThanFiveAttachments(): void
    {
        $path = $this->temporaryFile('contenido');
        $uploads = array_fill(0, 6, new MessageAttachmentUpload($path, 'informe.pdf', 'application/pdf', 9, 'pdf'));

        $this->expectException(InvalidMessageException::class);

        $this->sendService()(new SendMessageRequest(text: 'Resume esto', attachments: $uploads), UserMother::adminUser());
    }

    public function testRemovesTheStoredFilesWhenTheMessageIsRejected(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::startedBy($admin->id());
        $this->conversations->add($conversation);
        $path = $this->temporaryFile('contenido');

        try {
            $this->sendService()(new SendMessageRequest($conversation->id(), str_repeat('a', 4001), attachments: [new MessageAttachmentUpload($path, 'informe.pdf', 'application/pdf', 9, 'pdf')]), $admin);
            $this->fail('The message must be invalid');
        } catch (InvalidMessageException) {
        }

        $this->assertSame([], $this->attachmentStorage->pathsOf($conversation->id()));
    }

    private function temporaryFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'assistant');
        file_put_contents($path, $contents);

        return $path;
    }
}
