<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\Rename\RenameConversationRequest;
use App\Assistant\Application\Conversation\Rename\RenameConversationService;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\InvalidConversationTitleException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RenameConversationServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testTheOwnerRenamesTheConversation(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);

        $this->service()(new RenameConversationRequest($conversation->id(), 'Facturación de Acme'), $admin);

        $this->assertSame('Facturación de Acme', $this->conversations->find($conversation->id())?->title());
    }

    public function testAnInvalidTitleIsRejected(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);

        $this->expectException(InvalidConversationTitleException::class);

        $this->service()(new RenameConversationRequest($conversation->id(), ''), $admin);
    }

    public function testAnotherAdminIsDenied(): void
    {
        $conversation = ConversationMother::answered(UserMother::superAdminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new RenameConversationRequest($conversation->id(), 'Otro'), UserMother::adminUser());
    }

    public function testAMissingConversationIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(new RenameConversationRequest(ConversationId::generate(), 'Otro'), UserMother::adminUser());
    }

    private function service(): RenameConversationService
    {
        return new RenameConversationService($this->conversations, ConversationAuthorizerMother::create(), $this->clock);
    }
}
