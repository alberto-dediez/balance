<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Twig;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    /**
     * @var array<string, string|TranslatableMessage>
     */
    private array $users = [];

    public function __construct(private readonly UserRepository $userRepository) {}

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('user_id', $this->userId(...)),
            new TwigFilter('user_name', $this->userName(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [

        ];
    }

    public function userId(mixed $userId): UserId
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
