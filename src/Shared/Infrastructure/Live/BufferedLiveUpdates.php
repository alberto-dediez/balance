<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Live;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Live\LiveTopics;
use App\Shared\Application\Live\LiveUpdates;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\Service\ResetInterface;

class BufferedLiveUpdates implements LiveUpdates, ResetInterface
{
    /** @var array<string, true> */
    private array $contexts = [];

    /** @var array<string, true> */
    private array $keys = [];

    /** @var array<string, UserId> */
    private array $users = [];

    /** @var array<string, array{UserId, string}> */
    private array $userKeys = [];

    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {}

    public function changed(string $context): void
    {
        if ($this->enabled) {
            $this->contexts[$context] = true;
        }
    }

    public function touched(string $key): void
    {
        if ($this->enabled) {
            $this->keys[$key] = true;
        }
    }

    public function notifyUser(UserId $userId): void
    {
        if ($this->enabled) {
            $this->users[(string) $userId] = $userId;
        }
    }

    public function touchedForUser(UserId $userId, string $key): void
    {
        if ($this->enabled) {
            $this->userKeys[$userId . '|' . $key] = [$userId, $key];
        }
    }

    public function flush(): void
    {
        $contexts = array_map(strval(...), array_keys($this->contexts));
        $keys = array_map(strval(...), array_keys($this->keys));
        $users = $this->users;
        $userKeys = $this->userKeys;
        $this->reset();

        if ([] !== $contexts) {
            $this->publish(LiveTopics::ADMIN, ['key' => 'changes', 'contexts' => $contexts]);
        }

        foreach ($keys as $key) {
            $this->publish(LiveTopics::ADMIN, ['key' => $key]);
        }

        foreach ($users as $userId) {
            $this->publish(LiveTopics::ofUser($userId), ['key' => 'notifications']);
        }

        foreach ($userKeys as [$userId, $key]) {
            $this->publish(LiveTopics::ofUser($userId), ['key' => $key]);
        }
    }

    public function reset(): void
    {
        $this->contexts = [];
        $this->keys = [];
        $this->users = [];
        $this->userKeys = [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publish(string $topic, array $data): void
    {
        try {
            $this->hub->publish(new Update(
                topics: [$topic],
                data: json_encode($data, JSON_THROW_ON_ERROR),
                private: true,
            ));
        } catch (\Throwable $exception) {
            $this->logger->warning('The live update could not be published to Mercure', [
                'topic' => $topic,
                'exception' => $exception,
            ]);
        }
    }
}
