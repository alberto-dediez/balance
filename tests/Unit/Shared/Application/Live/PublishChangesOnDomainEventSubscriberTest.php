<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Live;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Shared\Application\Live\PublishChangesOnDomainEventSubscriber;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class PublishChangesOnDomainEventSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAnyEventMarksItsContext(): void
    {
        $liveUpdates = new SpyLiveUpdates();
        $sut = new PublishChangesOnDomainEventSubscriber($liveUpdates);

        $event = new AccountRegistered(AccountId::generate(), 'Acme', true);

        $this->assertTrue($sut->isSubscribedTo($event));

        $sut->handle($event);

        $this->assertSame(['security'], $liveUpdates->changed);
    }
}
