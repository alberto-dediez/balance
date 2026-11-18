<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Live;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Infrastructure\Live\BufferedLiveUpdates;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class BufferedLiveUpdatesTest extends TestCase
{
    /** @var list<Update> */
    private array $updates = [];

    public function testDoesNotPublishWhenDisabled(): void
    {
        $sut = $this->createSUT(enabled: false);

        $sut->changed('development');
        $sut->touched('request:1');
        $sut->notifyUser(UserId::generate());
        $sut->flush();

        $this->assertSame([], $this->updates);
    }

    public function testPublishesASingleChangeWithTheUnionOfContexts(): void
    {
        $sut = $this->createSUT();

        $sut->changed('development');
        $sut->changed('crm');
        $sut->changed('development');
        $sut->flush();

        $this->assertCount(1, $this->updates);
        $this->assertSame(['/live/admin'], $this->updates[0]->getTopics());
        $this->assertSame(['key' => 'changes', 'contexts' => ['development', 'crm']], json_decode($this->updates[0]->getData(), true));
    }

    public function testPublishesOneUpdatePerDistinctKey(): void
    {
        $sut = $this->createSUT();

        $sut->touched('agent-run-output:1');
        $sut->touched('agent-run-output:1');
        $sut->touched('request:2');
        $sut->flush();

        $this->assertCount(2, $this->updates);
        $this->assertSame(['key' => 'agent-run-output:1'], json_decode($this->updates[0]->getData(), true));
        $this->assertSame(['key' => 'request:2'], json_decode($this->updates[1]->getData(), true));
    }

    public function testNotifiesTheTopicOfTheUser(): void
    {
        $userId = UserId::generate();
        $sut = $this->createSUT();

        $sut->notifyUser($userId);
        $sut->notifyUser($userId);
        $sut->flush();

        $this->assertCount(1, $this->updates);
        $this->assertSame(['/live/users/' . $userId], $this->updates[0]->getTopics());
        $this->assertSame(['key' => 'notifications'], json_decode($this->updates[0]->getData(), true));
    }

    public function testTouchedForUserPublishesTheKeyOnTheTopicOfTheUser(): void
    {
        $userId = UserId::generate();
        $sut = $this->createSUT();

        $sut->touchedForUser($userId, 'assistant');
        $sut->touchedForUser($userId, 'assistant');
        $sut->flush();

        $this->assertCount(1, $this->updates);
        $this->assertSame(['/live/users/' . $userId], $this->updates[0]->getTopics());
        $this->assertSame(['key' => 'assistant'], json_decode($this->updates[0]->getData(), true));
        $this->assertTrue($this->updates[0]->isPrivate());
    }

    public function testEveryUpdateIsPrivate(): void
    {
        $sut = $this->createSUT();

        $sut->changed('crm');
        $sut->touched('request:1');
        $sut->notifyUser(UserId::generate());
        $sut->flush();

        $this->assertCount(3, $this->updates);

        foreach ($this->updates as $update) {
            $this->assertTrue($update->isPrivate());
            $this->assertArrayHasKey('key', json_decode($update->getData(), true));
        }
    }

    public function testASecondFlushDoesNotRepeatTheUpdates(): void
    {
        $sut = $this->createSUT();

        $sut->changed('crm');
        $sut->flush();
        $sut->flush();

        $this->assertCount(1, $this->updates);
    }

    public function testResetDiscardsThePendingUpdates(): void
    {
        $sut = $this->createSUT();

        $sut->changed('crm');
        $sut->touched('request:1');
        $sut->notifyUser(UserId::generate());
        $sut->reset();
        $sut->flush();

        $this->assertSame([], $this->updates);
    }

    public function testAHubFailureIsNotPropagatedNorStopsTheOtherUpdates(): void
    {
        $attempts = 0;
        $hub = new MockHub('https://hub/.well-known/mercure', new StaticTokenProvider('token'), function (Update $update) use (&$attempts): string {
            ++$attempts;

            if (1 === $attempts) {
                throw new \RuntimeException('Hub down');
            }

            $this->updates[] = $update;

            return 'id';
        });
        $sut = new BufferedLiveUpdates($hub, new NullLogger(), true);

        $sut->changed('crm');
        $sut->touched('request:1');
        $sut->flush();

        $this->assertSame(2, $attempts);
        $this->assertCount(1, $this->updates);
        $this->assertSame(['key' => 'request:1'], json_decode($this->updates[0]->getData(), true));
    }

    private function createSUT(bool $enabled = true): BufferedLiveUpdates
    {
        $hub = new MockHub('https://hub/.well-known/mercure', new StaticTokenProvider('token'), function (Update $update): string {
            $this->updates[] = $update;

            return 'id';
        });

        return new BufferedLiveUpdates($hub, new NullLogger(), $enabled);
    }
}
