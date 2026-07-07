<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LoggedUserTest extends TestCase
{
    public function testResolvesUserFromSecurityToken(): void
    {
        $user = UserMother::adminUser();
        $userRepository = new InMemoryUserRepository();
        $userRepository->add($user);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new SecurityUser($user));

        $sut = new LoggedUser($security, $userRepository);

        $this->assertTrue($user->id()->equals($sut()->id()));
    }

    public function testResetClearsMemoizedUser(): void
    {
        $user = UserMother::adminUser();
        $userRepository = new InMemoryUserRepository();
        $userRepository->add($user);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturnOnConsecutiveCalls(new SecurityUser($user), null);

        $sut = new LoggedUser($security, $userRepository);
        $sut();

        $sut->reset();

        $this->expectException(\RuntimeException::class);
        $sut();
    }

    public function testResetClearsForcedUser(): void
    {
        $user = UserMother::adminUser();
        $userRepository = new InMemoryUserRepository();

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $sut = new LoggedUser($security, $userRepository);
        $sut->forceUser($user);

        $sut->reset();

        $this->expectException(\RuntimeException::class);
        $sut();
    }
}
