<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Infrastructure\UI\Web;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Tests\Unit\Assistant\Application\Conversation\ConversationAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AssistantAccessTest extends TestCase
{
    public function testAnAdminCanUseTheEnabledAssistant(): void
    {
        $this->assertTrue($this->access(UserMother::adminUser())->isGranted());
    }

    public function testNobodyCanUseTheDisabledAssistant(): void
    {
        $this->assertFalse($this->access(UserMother::adminUser(), enabled: false)->isGranted());
    }

    public function testOwnersCannotUseTheAssistant(): void
    {
        $this->assertFalse($this->access(UserMother::ownerUser())->isGranted());
    }

    public function testAnonymousVisitorsCannotUseTheAssistant(): void
    {
        $this->assertFalse($this->access(null)->isGranted());
    }

    public function testAnImpersonatedUserCannotUseTheAssistant(): void
    {
        $this->assertFalse($this->access(UserMother::adminUser(), impersonator: UserMother::superAdminUser())->isGranted());
    }

    private function access(?User $user, bool $enabled = true, ?User $impersonator = null): AssistantAccess
    {
        $loggedUser = new LoggedUser($this->createMock(Security::class), new InMemoryUserRepository());
        $loggedUser->forceUser($user);
        $loggedUser->forceImpersonator($impersonator);

        return new AssistantAccess(new AssistantSettings(enabled: $enabled), ConversationAuthorizerMother::create(), $loggedUser);
    }
}
