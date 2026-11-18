<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Infrastructure\UI\Web\Shortcut;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Assistant\Infrastructure\UI\Web\Shortcut\AssistantShortcutProvider;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Tests\Unit\Assistant\Application\Conversation\ConversationAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AssistantShortcutProviderTest extends TestCase
{
    public function testAnAdminGetsTheShortcutOfThePanel(): void
    {
        $admin = UserMother::adminUser();

        $shortcuts = $this->provider($admin)->shortcuts($admin);

        $this->assertCount(1, $shortcuts);
    }

    public function testAnAdminImpersonatedByASuperAdminDoesNotGetTheShortcut(): void
    {
        $admin = UserMother::adminUser();
        $provider = $this->provider($admin, UserMother::superAdminUser());

        $this->assertFalse($provider->isAvailableTo($admin));
        $this->assertSame([], $provider->shortcuts($admin));
    }

    public function testAnOwnerDoesNotGetTheShortcut(): void
    {
        $owner = UserMother::ownerUser();

        $this->assertSame([], $this->provider($owner)->shortcuts($owner));
    }

    private function provider(User $user, ?User $impersonator = null): AssistantShortcutProvider
    {
        $loggedUser = new LoggedUser($this->createMock(Security::class), new InMemoryUserRepository());
        $loggedUser->forceUser($user);
        $loggedUser->forceImpersonator($impersonator);

        return new AssistantShortcutProvider(new AssistantAccess(new AssistantSettings(), ConversationAuthorizerMother::create(), $loggedUser));
    }
}
