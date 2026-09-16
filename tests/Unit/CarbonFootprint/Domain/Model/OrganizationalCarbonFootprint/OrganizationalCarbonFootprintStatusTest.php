<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus as Status;
use PHPUnit\Framework\TestCase;

final class OrganizationalCarbonFootprintStatusTest extends TestCase
{
    public function testAdvancedToMovesForward(): void
    {
        $this->assertSame(Status::Scope1Answered, Status::Started->advancedTo(Status::Scope1Answered));
        $this->assertSame(Status::Scope2Answered, Status::Scope1Answered->advancedTo(Status::Scope2Answered));
    }

    public function testAdvancedToNeverGoesBack(): void
    {
        $this->assertSame(Status::Scope2Answered, Status::Scope2Answered->advancedTo(Status::Scope1Answered));
    }

    public function testCompletedIsPreserved(): void
    {
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::Scope1Answered));
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::Scope2Answered));
    }

    public function testIsScope1Answered(): void
    {
        $this->assertFalse(Status::Started->isScope1Answered());
        $this->assertTrue(Status::Scope1Answered->isScope1Answered());
        $this->assertTrue(Status::Scope2Answered->isScope1Answered());
        $this->assertTrue(Status::Completed->isScope1Answered());
    }

    public function testIsScope2Answered(): void
    {
        $this->assertFalse(Status::Started->isScope2Answered());
        $this->assertFalse(Status::Scope1Answered->isScope2Answered());
        $this->assertTrue(Status::Scope2Answered->isScope2Answered());
        $this->assertTrue(Status::Completed->isScope2Answered());
    }

    public function testActiveStepKey(): void
    {
        $this->assertSame('scope-1', Status::Started->activeStepKey());
        $this->assertSame('scope-2', Status::Scope1Answered->activeStepKey());
        $this->assertSame('results', Status::Scope2Answered->activeStepKey());
        $this->assertSame('results', Status::Completed->activeStepKey());
    }

    public function testStepNumber(): void
    {
        $this->assertSame(1, Status::Started->stepNumber());
        $this->assertSame(2, Status::Scope1Answered->stepNumber());
        $this->assertSame(3, Status::Scope2Answered->stepNumber());
        $this->assertSame(3, Status::Completed->stepNumber());
    }
}
