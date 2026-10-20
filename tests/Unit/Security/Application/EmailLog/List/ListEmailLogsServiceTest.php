<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog\List;

use App\Security\Application\EmailLog\EmailLogAuthorizer;
use App\Security\Application\EmailLog\List\ListEmailLogsRequest;
use App\Security\Application\EmailLog\List\ListEmailLogsService;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmailLogsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testSuperAdminCanListEmailLogs(): void
    {
        $repository = EmailLogRepositoryMother::inMemory();
        $repository->add(EmailLogMother::sent());
        $repository->add(EmailLogMother::sent());

        $sut = new ListEmailLogsService($repository, new EmailLogAuthorizer());

        $response = $sut(new ListEmailLogsRequest(), UserMother::superAdminUser());

        $this->assertCount(2, $response->emailLogs);
        $this->assertEquals(2, $response->totalResults);
    }

    public function testAdminCannotListEmailLogs(): void
    {
        $sut = new ListEmailLogsService(EmailLogRepositoryMother::inMemory(), new EmailLogAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListEmailLogsRequest(), UserMother::adminUser());
    }
}
