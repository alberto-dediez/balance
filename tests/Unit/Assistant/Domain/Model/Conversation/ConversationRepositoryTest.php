<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ConversationRepositoryTest extends KernelTestCase
{
    public function testAddsFindsAndRemovesAConversation(): void
    {
        $repository = $this->createSUT();
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer(UserMother::adminUser()->id(), $messageId);

        $this->assertNull($repository->find($conversation->id()));

        $repository->add($conversation);
        $this->clear();

        $this->assertNotNull($repository->find($conversation->id()));
        $this->assertSame(MessageStatus::Pending, $repository->messageStatusOf($messageId));
        $this->assertNull($repository->messageStatusOf(MessageId::generate()));

        $repository->remove($conversation);
        $this->clear();

        $this->assertNull($repository->find($conversation->id()));
    }

    public function testFiltersTheConversationsWithAPendingMessageOlderThanALimit(): void
    {
        $repository = $this->createSUT();
        $userId = UserMother::adminUser()->id();
        $stale = ConversationMother::waitingForAnswer($userId, now: new \DateTimeImmutable('2026-11-11 09:00:00'));
        $fresh = ConversationMother::waitingForAnswer($userId, now: new \DateTimeImmutable('2026-11-11 09:58:00'));
        $answered = ConversationMother::answered($userId, now: new \DateTimeImmutable('2026-11-11 09:00:00'));

        foreach ([$stale, $fresh, $answered] as $conversation) {
            $repository->add($conversation);
        }
        $this->clear();

        $found = $repository->findByFilter(new ConversationFilter(pendingMessageBefore: new \DateTimeImmutable('2026-11-11 09:50:00'), limit: 0));

        $this->assertSame([(string) $stale->id()], array_map(static fn(Conversation $conversation): string => (string) $conversation->id(), $found));
    }

    public function testSumsTheCostOfTheMessagesOfAUserSinceADate(): void
    {
        $repository = $this->createSUT();
        $user = UserMother::adminUser();
        $repository->add(ConversationMother::answered($user->id(), cost: '1.250000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));
        $repository->add(ConversationMother::answered($user->id(), cost: '3.000000', now: new \DateTimeImmutable('2026-11-10 23:00:00')));
        $repository->add(ConversationMother::answered(UserMother::superAdminUser()->id(), cost: '2.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));
        $this->clear();

        $this->assertSame('1.250000', $repository->costOfUserSince($user->id(), new \DateTimeImmutable('2026-11-11 00:00:00')));
    }

    public function testGroupsTheUsageOfTheAssistantMessagesByDayUserAndModel(): void
    {
        $repository = $this->createSUT();
        $user = UserMother::adminUser();
        $repository->add(ConversationMother::answered($user->id(), cost: '1.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));
        $repository->add(ConversationMother::answered($user->id(), cost: '0.500000', now: new \DateTimeImmutable('2026-11-11 18:00:00')));
        $repository->add(ConversationMother::answered($user->id(), cost: '2.000000', now: new \DateTimeImmutable('2026-11-10 09:00:00')));
        $this->clear();

        $rows = $repository->usageRowsBetween(new \DateTimeImmutable('2026-11-11 00:00:00'), new \DateTimeImmutable('2026-11-12 00:00:00'));

        $this->assertCount(1, $rows);
        $this->assertSame('2026-11-11', $rows[0]->day->format('Y-m-d'));
        $this->assertSame(2, $rows[0]->messages);
        $this->assertSame(200, $rows[0]->inputTokens);
        $this->assertSame(40, $rows[0]->outputTokens);
        $this->assertSame('1.500000', $rows[0]->costUsd);
        $this->assertSame('in-memory', $rows[0]->model);
        $this->assertCount(2, $repository->usageRowsBetween(null, null));
    }

    protected function clear(): void {}

    abstract protected function createSUT(): ConversationRepository;
}
