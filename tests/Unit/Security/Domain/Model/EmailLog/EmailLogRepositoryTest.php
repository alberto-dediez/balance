<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\EmailLog;

use App\Security\Domain\Model\EmailLog\EmailLogFilter;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EmailLogRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $emailLog = EmailLogMother::sent();

        $this->assertNull($repository->find($emailLog->id()));

        $repository->add($emailLog);

        $recovered = $repository->find($emailLog->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($emailLog->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $emailLog = EmailLogMother::sent();
        $repository->add($emailLog);

        $repository->remove($emailLog);

        $this->assertNull($repository->find($emailLog->id()));
    }

    public function testFindByFilterByRecipientEmailOrName(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::from(recipientEmail: 'alice@example.com', recipientName: 'Alice'));
        $repository->add(EmailLogMother::from(recipientEmail: 'bob@example.com', recipientName: 'Roberto Alicante'));
        $repository->add(EmailLogMother::from(recipientEmail: 'carol@example.com', recipientName: 'Carol'));

        $this->assertCount(2, $repository->findByFilter(new EmailLogFilter(recipient: 'ali', limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new EmailLogFilter(recipient: 'carol@', limit: 0)));
    }

    public function testFindByFilterBySubject(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::from(subject: 'Recupera tu contraseña'));
        $repository->add(EmailLogMother::from(subject: 'Código de acceso'));

        $result = $repository->findByFilter(new EmailLogFilter(subject: 'contraseña', limit: 0));

        $this->assertCount(1, $result);
        $this->assertEquals('Recupera tu contraseña', $result[0]->subject());
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sent());
        $repository->add(EmailLogMother::delivered());
        $repository->add(EmailLogMother::failed('boom'));

        $this->assertCount(1, $repository->findByFilter(new EmailLogFilter(status: EmailLogStatus::Delivered, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new EmailLogFilter(status: EmailLogStatus::Failed, limit: 0)));
    }

    public function testFindByFilterByProvider(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sent(provider: 'sendgrid'));
        $repository->add(EmailLogMother::sent(provider: 'smtp'));

        $result = $repository->findByFilter(new EmailLogFilter(provider: 'smtp', limit: 0));

        $this->assertCount(1, $result);
        $this->assertEquals('smtp', $result[0]->provider());
    }

    public function testFindByFilterByDateRange(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-01 10:00:00')));
        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-10 10:00:00')));
        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-20 10:00:00')));

        $result = $repository->findByFilter(new EmailLogFilter(
            sentFrom: new \DateTimeImmutable('2026-10-05 00:00:00'),
            sentUntil: new \DateTimeImmutable('2026-10-15 00:00:00'),
            limit: 0,
        ));

        $this->assertCount(1, $result);
        $this->assertEquals(new \DateTimeImmutable('2026-10-10 10:00:00'), $result[0]->sentAt());
    }

    public function testDefaultOrderIsSentAtDescending(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-01 10:00:00')));
        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-20 10:00:00')));
        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-10 10:00:00')));

        $result = $repository->findByFilter(new EmailLogFilter(limit: 0));

        $this->assertEquals(new \DateTimeImmutable('2026-10-20 10:00:00'), $result[0]->sentAt());
        $this->assertEquals(new \DateTimeImmutable('2026-10-01 10:00:00'), $result[2]->sentAt());
    }

    public function testOrderByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sent());
        $repository->add(EmailLogMother::failed('boom'));
        $repository->add(EmailLogMother::delivered());

        $filter = new EmailLogFilter(limit: 0);
        $filter->orderBy = 'status';
        $filter->orderWay = 'ASC';

        $result = $repository->findByFilter($filter);

        $this->assertEquals(EmailLogStatus::Delivered, $result[0]->status());
        $this->assertEquals(EmailLogStatus::Sent, $result[2]->status());
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmailLogMother::sent());
        $repository->add(EmailLogMother::sent());
        $repository->add(EmailLogMother::sent());

        $filter = new EmailLogFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertEquals(3, $repository->totalResultsByFilter($filter));
    }

    public function testFindSentBeforeIsExclusive(): void
    {
        $repository = $this->createSUT();

        $limit = new \DateTimeImmutable('2026-10-10 10:00:00');

        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-01 10:00:00')));
        $repository->add(EmailLogMother::sentAt($limit));
        $repository->add(EmailLogMother::sentAt(new \DateTimeImmutable('2026-10-20 10:00:00')));

        $result = $repository->findSentBefore($limit);

        $this->assertCount(1, $result);
        $this->assertEquals(new \DateTimeImmutable('2026-10-01 10:00:00'), $result[0]->sentAt());
    }

    abstract protected function createSUT(): EmailLogRepository;
}
