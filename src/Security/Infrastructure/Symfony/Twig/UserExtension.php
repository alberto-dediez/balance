<?php

namespace App\Security\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Translation\TranslatableMessage;

class UserExtension extends AbstractExtension
{
    private array $users = [];

    public function __construct(private UserRepository $userRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('user_id', [$this, 'userId']),
            new TwigFilter('user_name', [$this, 'userName'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function userId($userId): UserId
    {
        return new UserId((string) $userId);
    }

    public function userName(UserId $userId): string|TranslatableMessage
    {
        if (!isset($this->users[(string) $userId])) {
            $user = $this->userRepository->find($userId);

            $username = null == $user ? new TranslatableMessage('<s>{userId}</s>', ['{userId}' => (string) $userId]) : (string) $user;

            $this->users[(string) $userId] = $username;
        }

        return $this->users[(string) $userId];
    }
}
