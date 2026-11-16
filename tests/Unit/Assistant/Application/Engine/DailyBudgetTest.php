<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Application\Engine\DailyLimitReachedException;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use PHPUnit\Framework\TestCase;

class DailyBudgetTest extends TestCase
{
    public function testSumsOnlyTheCostOfTodayOfTheUser(): void
    {
        $user = UserMother::adminUser();
        $conversations = new InMemoryConversationRepository();
        $conversations->add(ConversationMother::answered($user->id(), cost: '1.250000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));
        $conversations->add(ConversationMother::answered($user->id(), cost: '3.000000', now: new \DateTimeImmutable('2026-11-10 23:00:00')));
        $conversations->add(ConversationMother::answered(UserMother::superAdminUser()->id(), cost: '2.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));

        $budget = $this->budget($conversations, '5');

        $this->assertSame('1.250000', $budget->spentToday($user));
        $budget->assertAvailable($user);
    }

    public function testRejectsWhenTheLimitIsReached(): void
    {
        $user = UserMother::adminUser();
        $conversations = new InMemoryConversationRepository();
        $conversations->add(ConversationMother::answered($user->id(), cost: '5.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));

        $this->expectException(DailyLimitReachedException::class);

        $this->budget($conversations, '5')->assertAvailable($user);
    }

    public function testAZeroLimitMeansNoLimit(): void
    {
        $user = UserMother::adminUser();
        $conversations = new InMemoryConversationRepository();
        $conversations->add(ConversationMother::answered($user->id(), cost: '50.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));

        $this->budget($conversations, '0')->assertAvailable($user);

        $this->addToAssertionCount(1);
    }

    private function budget(InMemoryConversationRepository $conversations, string $limit): DailyBudget
    {
        return new DailyBudget($conversations, new InMemoryActionPlanRepository(), new AssistantSettings(dailyCostLimitUsd: $limit), new FixedClock(new \DateTimeImmutable('2026-11-11 12:00:00')));
    }
}
