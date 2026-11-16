<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\Remove\RemoveConversationRequest;
use App\Assistant\Application\Conversation\Remove\RemoveConversationService;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemoveConversationServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testRemovesTheConversationWithItsPlansAndFiles(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $plan = ActionPlanMother::proposed($admin->id(), $conversation->id());
        $this->conversations->add($conversation);
        $this->plans->add($plan);
        $this->attachmentStorage->put((string) $conversation->id() . '/a.pdf', 'contenido');

        $this->service()(new RemoveConversationRequest($conversation->id()), $admin);

        $this->assertNull($this->conversations->find($conversation->id()));
        $this->assertNull($this->plans->find($plan->id()));
        $this->assertSame([], $this->attachmentStorage->pathsOf($conversation->id()));
    }

    public function testRejectsAConversationWithAPendingMessage(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::waitingForAnswer($admin->id());
        $this->conversations->add($conversation);

        $this->expectException(ConversationBusyException::class);

        $this->service()(new RemoveConversationRequest($conversation->id()), $admin);
    }

    public function testRejectsAConversationWithAPlanInExecution(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::answered($admin->id());
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::executing($admin->id(), $conversation->id()));

        $this->expectException(ConversationBusyException::class);

        $this->service()(new RemoveConversationRequest($conversation->id()), $admin);
    }

    public function testAnotherAdminIsDenied(): void
    {
        $conversation = ConversationMother::answered(UserMother::superAdminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new RemoveConversationRequest($conversation->id()), UserMother::adminUser());
    }

    private function service(): RemoveConversationService
    {
        return new RemoveConversationService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->attachmentStorage, $this->clock);
    }
}
