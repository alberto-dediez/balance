<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\DownloadAttachment\DownloadConversationAttachmentRequest;
use App\Assistant\Application\Conversation\DownloadAttachment\DownloadConversationAttachmentService;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentDraft;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadConversationAttachmentServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private User $admin;

    private Conversation $conversation;

    private MessageAttachmentId $attachmentId;

    protected function setUp(): void
    {
        $this->setUpAssistant();

        $this->admin = UserMother::adminUser();
        $this->attachmentId = MessageAttachmentId::generate();
        $this->conversation = ConversationMother::startedBy($this->admin->id());
        $path = sprintf('%s/%s.pdf', $this->conversation->id(), $this->attachmentId);
        $this->attachmentStorage->put($path, 'contenido');
        $this->conversation->addUserMessage(MessageId::generate(), 'Mira esto', '', '', 'UTC', new \DateTimeImmutable(), [new MessageAttachmentDraft($this->attachmentId, 'informe.pdf', 'application/pdf', 9, $path)]);
        $this->conversations->add($this->conversation);
    }

    public function testTheOwnerDownloadsTheFile(): void
    {
        $response = $this->service()(new DownloadConversationAttachmentRequest($this->conversation->id(), $this->attachmentId), $this->admin);

        $this->assertSame('contenido', $response->contents);
        $this->assertSame('informe.pdf', $response->fileName);
        $this->assertSame('application/pdf', $response->mimeType);
        $this->assertSame(9, $response->size);
    }

    public function testAnotherAdminIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new DownloadConversationAttachmentRequest($this->conversation->id(), $this->attachmentId), UserMother::superAdminUser());
    }

    public function testAMissingAttachmentIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(new DownloadConversationAttachmentRequest($this->conversation->id(), MessageAttachmentId::generate()), $this->admin);
    }

    public function testAFileRemovedFromTheDiskIsNotFound(): void
    {
        $this->attachmentStorage->reset();

        $this->expectException(\InvalidArgumentException::class);

        $this->service()(new DownloadConversationAttachmentRequest($this->conversation->id(), $this->attachmentId), $this->admin);
    }

    private function service(): DownloadConversationAttachmentService
    {
        return new DownloadConversationAttachmentService($this->conversations, $this->attachmentStorage, ConversationAuthorizerMother::create());
    }
}
