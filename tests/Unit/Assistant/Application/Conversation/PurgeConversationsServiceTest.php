<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\Purge\PurgeConversationsRequest;
use App\Assistant\Application\Conversation\Purge\PurgeConversationsService;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PurgeConversationsServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testRemovesTheInactiveConversationsWithTheirPlans(): void
    {
        $admin = UserMother::adminUser();
        $old = ConversationMother::answered($admin->id(), now: new \DateTimeImmutable('2026-08-01 10:00:00'));
        $recent = ConversationMother::answered($admin->id(), now: new \DateTimeImmutable('2026-10-01 10:00:00'));
        $oldPlan = ActionPlanMother::proposed($admin->id(), $old->id());
        $recentPlan = ActionPlanMother::proposed($admin->id(), $recent->id());
        $this->conversations->add($old);
        $this->conversations->add($recent);
        $this->plans->add($oldPlan);
        $this->plans->add($recentPlan);

        $response = (new PurgeConversationsService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->settings, $this->attachmentStorage))(
            new PurgeConversationsRequest(new \DateTimeImmutable('2026-11-11 10:00:00')),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->purged);
        $this->assertNull($this->conversations->find($old->id()));
        $this->assertNull($this->plans->find($oldPlan->id()));
        $this->assertNotNull($this->conversations->find($recent->id()));
        $this->assertNotNull($this->plans->find($recentPlan->id()));
    }

    public function testRemovesTheAttachmentFilesOfThePurgedConversations(): void
    {
        $admin = UserMother::adminUser();
        $old = ConversationMother::answered($admin->id(), now: new \DateTimeImmutable('2026-08-01 10:00:00'));
        $this->conversations->add($old);
        $this->attachmentStorage->put((string) $old->id() . '/a.pdf', 'contenido');

        (new PurgeConversationsService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->settings, $this->attachmentStorage))(
            new PurgeConversationsRequest(new \DateTimeImmutable('2026-11-11 10:00:00')),
            UserMother::systemUser(),
        );

        $this->assertSame([], $this->attachmentStorage->pathsOf($old->id()));
    }
}
