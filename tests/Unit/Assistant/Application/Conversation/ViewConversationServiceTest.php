<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\View\ViewConversationRequest;
use App\Assistant\Application\Conversation\View\ViewConversationService;
use App\Assistant\Application\Conversation\ViewStatus\ViewConversationStatusRequest;
use App\Assistant\Application\Conversation\ViewStatus\ViewConversationStatusService;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewConversationServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant(now: new \DateTimeImmutable('2026-11-11 10:30:00'));
        $this->tools = [FakeAssistantTool::read('list_tasks')];
    }

    public function testReturnsTheThreadWithTheExecutablePlan(): void
    {
        $admin = UserMother::adminUser();
        $planId = ActionPlanId::generate();
        $conversation = ConversationMother::withPlan($admin->id(), $planId);
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::proposed($admin->id(), $conversation->id(), id: $planId));

        $response = $this->service()(new ViewConversationRequest($conversation->id()), $admin);

        $this->assertCount(2, $response->entries);
        $this->assertNull($response->entries[0]->plan);
        $this->assertTrue($response->entries[1]->executable);
        $this->assertFalse($response->busy);
        $this->assertSame('5.000000', $response->dailyLimit);
        $this->assertSame(['list_tasks' => 'Etiqueta de list_tasks'], $response->toolLabels);
    }

    public function testAnExpiredPlanIsNotExecutable(): void
    {
        $this->setUpAssistant(now: new \DateTimeImmutable('2026-11-11 12:00:00'));
        $admin = UserMother::adminUser();
        $planId = ActionPlanId::generate();
        $conversation = ConversationMother::withPlan($admin->id(), $planId);
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::proposed($admin->id(), $conversation->id(), id: $planId));

        $this->assertFalse($this->service()(new ViewConversationRequest($conversation->id()), $admin)->entries[1]->executable);
    }

    public function testTheStatusFingerprintChangesWithTheThread(): void
    {
        $admin = UserMother::adminUser();
        $conversation = ConversationMother::withQuestion($admin->id());
        $this->conversations->add($conversation);
        $status = new ViewConversationStatusService($this->conversations, $this->plans, ConversationAuthorizerMother::create());

        $before = $status(new ViewConversationStatusRequest($conversation->id()), $admin);
        $conversation->addPendingAssistantMessage(MessageId::generate(), MessageKind::Text, new \DateTimeImmutable());
        $after = $status(new ViewConversationStatusRequest($conversation->id()), $admin);

        $this->assertNotSame($before->fingerprint, $after->fingerprint);
        $this->assertFalse($before->busy);
        $this->assertTrue($after->busy);
    }

    public function testTheThreadCarriesTheSameFingerprintAsTheStatus(): void
    {
        $admin = UserMother::adminUser();
        $planId = ActionPlanId::generate();
        $conversation = ConversationMother::withPlan($admin->id(), $planId);
        $this->conversations->add($conversation);
        $this->plans->add(ActionPlanMother::proposed($admin->id(), $conversation->id(), id: $planId));
        $status = new ViewConversationStatusService($this->conversations, $this->plans, ConversationAuthorizerMother::create());

        $view = $this->service()(new ViewConversationRequest($conversation->id()), $admin);

        $this->assertNotSame('', $view->fingerprint);
        $this->assertSame($status(new ViewConversationStatusRequest($conversation->id()), $admin)->fingerprint, $view->fingerprint);
    }

    public function testTheConversationOfAnotherUserIsDeniedEvenToSuperAdmins(): void
    {
        $conversation = ConversationMother::answered(UserMother::adminUser()->id());
        $this->conversations->add($conversation);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new ViewConversationRequest($conversation->id()), UserMother::superAdminUser());
    }

    private function service(): ViewConversationService
    {
        return new ViewConversationService($this->conversations, $this->plans, ConversationAuthorizerMother::create(), $this->toolbox(), $this->budget(), $this->settings, $this->clock);
    }
}
