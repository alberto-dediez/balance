<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog\Purge;

use App\Security\Application\EmailLog\Purge\PurgeEmailLogsRequest;
use App\Security\Application\EmailLog\Purge\PurgeEmailLogsService;
use App\Security\Domain\Model\EmailLog\EmailLogRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PurgeEmailLogsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItRemovesEmailLogsOlderThanRetention(): void
    {
        $repository = EmailLogRepositoryMother::inMemory();
        $now = new \DateTimeImmutable('2026-10-20 03:00:00');

        $old = EmailLogMother::sentAt($now->modify('-181 days'));
        $recent = EmailLogMother::sentAt($now->modify('-179 days'));
        $today = EmailLogMother::sentAt($now);

        $repository->add($old);
        $repository->add($recent);
        $repository->add($today);

        $sut = new PurgeEmailLogsService($repository, 180);

        $response = $sut(new PurgeEmailLogsRequest($now), UserMother::systemUser());

        $this->assertSame(1, $response->removed);
        $this->assertNull($repository->find($old->id()));
        $this->assertNotNull($repository->find($recent->id()));
        $this->assertNotNull($repository->find($today->id()));
        $this->assertInstanceOf(EmailLogRemoved::class, $this->spySubscriber->lastEvent());
    }
}
