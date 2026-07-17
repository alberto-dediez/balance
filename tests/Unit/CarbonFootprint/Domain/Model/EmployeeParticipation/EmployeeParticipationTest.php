<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class EmployeeParticipationTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterStartsOpen(): void
    {
        $participation = EmployeeParticipationMother::registeredEmployeeParticipation();

        $this->assertFalse($participation->status()->isClosed());
        $this->assertNull($participation->totalEmployees());
        $this->assertNull($participation->confidenceLevel());
    }

    public function testUpdateStoresTotalEmployeesAndConfidenceLevel(): void
    {
        $participation = EmployeeParticipationMother::registeredEmployeeParticipation();

        $participation->update(25, ConfidenceLevel::Ninety);

        $this->assertSame(25, $participation->totalEmployees());
        $this->assertSame(ConfidenceLevel::Ninety, $participation->confidenceLevel());
    }

    public function testCloseStoresMarginOfError(): void
    {
        $participation = EmployeeParticipationMother::registeredEmployeeParticipation();

        $participation->close(0.05);

        $this->assertTrue($participation->status()->isClosed());
        $this->assertSame(0.05, $participation->marginOfError());
        $this->assertNotNull($participation->closedAt());
    }

    public function testReopenResetsClosureData(): void
    {
        $participation = EmployeeParticipationMother::registeredEmployeeParticipation();
        $participation->update(25, ConfidenceLevel::Ninety);
        $participation->close(0.05);

        $participation->reopen();

        $this->assertFalse($participation->status()->isClosed());
        $this->assertNull($participation->marginOfError());
        $this->assertNull($participation->closedAt());
        $this->assertSame(25, $participation->totalEmployees());
    }

    public function testReopenIsNoOpWhenOpen(): void
    {
        $participation = EmployeeParticipationMother::registeredEmployeeParticipation();
        $updatedAt = $participation->updatedAt();

        $participation->reopen();

        $this->assertSame($updatedAt, $participation->updatedAt());
    }
}
