<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Usage;

use App\Assistant\Application\Usage\View\ViewAssistantUsageRequest;
use App\Assistant\Application\Usage\View\ViewAssistantUsageService;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UserFinder;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Period\DailyPeriod;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Application\Conversation\ConversationAuthorizerMother;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ViewAssistantUsageServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private User $admin;

    private User $superAdmin;

    protected function setUp(): void
    {
        $this->setUpAssistant();

        $this->admin = UserMother::adminUser();
        $this->superAdmin = UserMother::superAdminUser();
    }

    public function testSumsMessagesAndPlansByDayUserAndModel(): void
    {
        $this->conversations->add(ConversationMother::answered($this->admin->id(), cost: '1.000000', now: new \DateTimeImmutable('2026-11-11 08:00:00')));
        $this->conversations->add(ConversationMother::answered($this->superAdmin->id(), cost: '0.500000', now: new \DateTimeImmutable('2026-11-11 09:00:00')));
        $this->conversations->add(ConversationMother::answered($this->admin->id(), cost: '2.000000', now: new \DateTimeImmutable('2026-11-10 09:00:00')));
        $plan = ActionPlanMother::executing($this->admin->id(), now: new \DateTimeImmutable('2026-11-11 10:00:00'));
        $plan->finish(new AssistantUsage('in-memory', 50, 10, 0, 0, '0.250000', 5), new \DateTimeImmutable('2026-11-11 10:01:00'));
        $this->plans->add($plan);

        $response = $this->service()(new ViewAssistantUsageRequest(DailyPeriod::week(new \DateTimeImmutable('2026-11-11'))), $this->admin);

        $this->assertSame(4, $response->totals->messages);
        $this->assertSame('3.750000', $response->totals->costUsd);
        $this->assertSame(['2026-11-11', '2026-11-10'], array_keys($response->days));
        $this->assertSame('1.750000', $response->days['2026-11-11']->costUsd);
        $this->assertSame(['in-memory'], array_keys($response->models));
        $this->assertCount(2, $response->users);
        $this->assertArrayHasKey('---', $response->users);
        $this->assertSame(350, $response->totals->inputTokens);
    }

    public function testThePeriodAllIncludesEverything(): void
    {
        $this->conversations->add(ConversationMother::answered($this->admin->id(), now: new \DateTimeImmutable('2025-01-01 08:00:00')));
        $this->conversations->add(ConversationMother::answered($this->admin->id(), now: new \DateTimeImmutable('2026-11-11 08:00:00')));

        $response = $this->service()(new ViewAssistantUsageRequest(DailyPeriod::all()), $this->admin);

        $this->assertSame(2, $response->totals->messages);
        $this->assertCount(2, $response->days);
    }

    public function testAnEmptyPeriodHasNoUsage(): void
    {
        $response = $this->service()(new ViewAssistantUsageRequest(DailyPeriod::all()), $this->admin);

        $this->assertTrue($response->totals->isEmpty());
    }

    public function testANonAdministratorIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new ViewAssistantUsageRequest(DailyPeriod::all()), UserMother::ownerUser());
    }

    private function service(): ViewAssistantUsageService
    {
        $users = UserRepositoryMother::inMemory();
        $users->add($this->admin);

        return new ViewAssistantUsageService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), new UserFinder($users));
    }
}
