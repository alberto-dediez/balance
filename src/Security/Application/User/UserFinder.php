<?php

declare(strict_types=1);

namespace App\Security\Application\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Contracts\Service\ResetInterface;

class UserFinder implements ResetInterface
{
    /**
     * @var array<string, ?User>
     */
    private array $users = [];

    public function __construct(private readonly UserRepository $repository) {}

    public function find(UserId $userId): ?User
    {
        $key = (string) $userId;

        if (!array_key_exists($key, $this->users)) {
            $this->users[$key] = $this->repository->find($userId);
        }

        return $this->users[$key];
    }

    public function reset(): void
    {
        $this->users = [];
    }
}
