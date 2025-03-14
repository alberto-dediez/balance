<?php

namespace App\Security\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;

class UserExtension extends AbstractExtension
{
    private array $users = [];

    public function __construct(private UserRepository $userRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('user_name', [$this, 'userName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function userName(UserId $userId): ?User
    {
        if (!isset($users[(string) $userId])) {
            $users[(string) $userId] = $this->userRepository->find($userId);
        }

        return $users[(string) $userId];
    }
}
