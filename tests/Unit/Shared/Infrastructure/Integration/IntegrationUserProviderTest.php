<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Infrastructure\Integration\IntegrationUserNotAvailable;
use App\Shared\Infrastructure\Integration\IntegrationUserProvider;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class IntegrationUserProviderTest extends TestCase
{
    private InMemoryUserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
    }

    public function testEmptyUsernameIsNotAvailable(): void
    {
        $this->expectException(IntegrationUserNotAvailable::class);

        (new IntegrationUserProvider($this->userRepository, ''))->user();
    }

    public function testUnknownUserIsNotAvailable(): void
    {
        $this->expectException(IntegrationUserNotAvailable::class);

        (new IntegrationUserProvider($this->userRepository, 'integration'))->user();
    }

    public function testUserWithoutAdminRoleIsNotAvailable(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRole('integration', Role::Owner));

        $this->expectException(IntegrationUserNotAvailable::class);

        (new IntegrationUserProvider($this->userRepository, 'integration'))->user();
    }

    public function testAdminUserIsReturned(): void
    {
        $user = UserMother::fromUsernameAndRole('integration', Role::Admin);
        $this->userRepository->add($user);

        $this->assertSame($user, (new IntegrationUserProvider($this->userRepository, 'integration'))->user());
    }

    public function testSuperAdminUserIsReturned(): void
    {
        $user = UserMother::fromUsernameAndRole('integration', Role::SuperAdmin);
        $this->userRepository->add($user);

        $this->assertSame($user, (new IntegrationUserProvider($this->userRepository, 'integration'))->user());
    }
}
