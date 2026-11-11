<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\List;

use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Application\ApiToken\List\ListApiTokensRequest;
use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ListApiTokensServiceTest extends TestCase
{
    public function testListsOwnTokens(): void
    {
        [$repository, $userRepository, $user] = $this->scenario();

        $result = $this->createSUT($repository, $userRepository)(new ListApiTokensRequest(userId: $user->id()), $user);

        $this->assertCount(2, $result->apiTokens);
        $this->assertSame(2, $result->totalResults);
    }

    public function testAdminListsTokensOfAnotherUser(): void
    {
        [$repository, $userRepository, $user] = $this->scenario();

        $result = $this->createSUT($repository, $userRepository)(new ListApiTokensRequest(userId: $user->id()), UserMother::adminUser());

        $this->assertCount(2, $result->apiTokens);
    }

    public function testAnotherUserCannotListThem(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        [$repository, $userRepository, $user] = $this->scenario();

        $this->createSUT($repository, $userRepository)(new ListApiTokensRequest(userId: $user->id()), UserMother::fromUsername('other@example.com'));
    }

    public function testAnAdminCannotListTheTokensOfASuperAdmin(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = ApiTokenRepositoryMother::inMemory();
        $userRepository = UserRepositoryMother::inMemory();
        $superAdmin = UserMother::superAdminUser();
        $userRepository->add($superAdmin);
        $repository->add(ApiTokenMother::active($superAdmin->id()));

        $this->createSUT($repository, $userRepository)(new ListApiTokensRequest(userId: $superAdmin->id()), UserMother::adminUser());
    }

    public function testNullUserIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        [$repository, $userRepository] = $this->scenario();

        $this->createSUT($repository, $userRepository)(new ListApiTokensRequest(), UserMother::adminUser());
    }

    /**
     * @return array{InMemoryApiTokenRepository, UserRepository, User}
     */
    private function scenario(): array
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $userRepository = UserRepositoryMother::inMemory();
        $user = UserMother::registeredUser();
        $userRepository->add($user);

        $repository->add(ApiTokenMother::active($user->id()));
        $repository->add(ApiTokenMother::revoked($user->id()));
        $repository->add(ApiTokenMother::active(UserMother::fromUsername('other@example.com')->id()));

        return [$repository, $userRepository, $user];
    }

    private function createSUT(InMemoryApiTokenRepository $repository, UserRepository $userRepository): ListApiTokensService
    {
        return new ListApiTokensService(
            repository: $repository,
            authorizer: new ApiTokenAuthorizer($userRepository),
            userRepository: $userRepository,
        );
    }
}
