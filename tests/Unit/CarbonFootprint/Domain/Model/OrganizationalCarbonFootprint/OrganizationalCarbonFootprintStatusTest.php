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
        $this->assertSame(Status::Scope3Answered, Status::Scope2Answered->advancedTo(Status::Scope3Answered));
        $this->assertSame(Status::ProvidersAnswered, Status::Scope3Answered->advancedTo(Status::ProvidersAnswered));
        $this->assertSame(Status::EmployeesAnswered, Status::ProvidersAnswered->advancedTo(Status::EmployeesAnswered));
    }

    public function testAdvancedToNeverGoesBack(): void
    {
        $this->assertSame(Status::Scope2Answered, Status::Scope2Answered->advancedTo(Status::Scope1Answered));
        $this->assertSame(Status::Scope3Answered, Status::Scope3Answered->advancedTo(Status::Scope2Answered));
        $this->assertSame(Status::ProvidersAnswered, Status::ProvidersAnswered->advancedTo(Status::Scope3Answered));
        $this->assertSame(Status::EmployeesAnswered, Status::EmployeesAnswered->advancedTo(Status::ProvidersAnswered));
    }

    public function testCompletedIsPreserved(): void
    {
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::Scope1Answered));
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::Scope2Answered));
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::Scope3Answered));
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::ProvidersAnswered));
        $this->assertSame(Status::Completed, Status::Completed->advancedTo(Status::EmployeesAnswered));
    }

    public function testIsScope1Answered(): void
    {
        $this->assertFalse(Status::Started->isScope1Answered());
        $this->assertTrue(Status::Scope1Answered->isScope1Answered());
        $this->assertTrue(Status::Scope2Answered->isScope1Answered());
        $this->assertTrue(Status::Scope3Answered->isScope1Answered());
        $this->assertTrue(Status::Completed->isScope1Answered());
    }

    public function testIsScope2Answered(): void
    {
        $this->assertFalse(Status::Started->isScope2Answered());
        $this->assertFalse(Status::Scope1Answered->isScope2Answered());
        $this->assertTrue(Status::Scope2Answered->isScope2Answered());
        $this->assertTrue(Status::Scope3Answered->isScope2Answered());
        $this->assertTrue(Status::Completed->isScope2Answered());
    }

    public function testIsScope3Answered(): void
    {
        $this->assertFalse(Status::Started->isScope3Answered());
        $this->assertFalse(Status::Scope1Answered->isScope3Answered());
        $this->assertFalse(Status::Scope2Answered->isScope3Answered());
        $this->assertTrue(Status::Scope3Answered->isScope3Answered());
        $this->assertTrue(Status::ProvidersAnswered->isScope3Answered());
        $this->assertTrue(Status::Completed->isScope3Answered());
    }

    public function testIsProvidersAnswered(): void
    {
        $this->assertFalse(Status::Started->isProvidersAnswered());
        $this->assertFalse(Status::Scope1Answered->isProvidersAnswered());
        $this->assertFalse(Status::Scope2Answered->isProvidersAnswered());
        $this->assertFalse(Status::Scope3Answered->isProvidersAnswered());
        $this->assertTrue(Status::ProvidersAnswered->isProvidersAnswered());
        $this->assertTrue(Status::EmployeesAnswered->isProvidersAnswered());
        $this->assertTrue(Status::Completed->isProvidersAnswered());
    }

    public function testIsEmployeesAnswered(): void
    {
        $this->assertFalse(Status::Started->isEmployeesAnswered());
        $this->assertFalse(Status::Scope1Answered->isEmployeesAnswered());
        $this->assertFalse(Status::Scope2Answered->isEmployeesAnswered());
        $this->assertFalse(Status::Scope3Answered->isEmployeesAnswered());
        $this->assertFalse(Status::ProvidersAnswered->isEmployeesAnswered());
        $this->assertTrue(Status::EmployeesAnswered->isEmployeesAnswered());
        $this->assertTrue(Status::Completed->isEmployeesAnswered());
    }

    public function testActiveStepKey(): void
    {
        $this->assertSame('scope-1', Status::Started->activeStepKey());
        $this->assertSame('scope-2', Status::Scope1Answered->activeStepKey());
        $this->assertSame('scope-3', Status::Scope2Answered->activeStepKey());
        $this->assertSame('providers', Status::Scope3Answered->activeStepKey());
        $this->assertSame('employees', Status::ProvidersAnswered->activeStepKey());
        $this->assertSame('mitigation-plan', Status::EmployeesAnswered->activeStepKey());
        $this->assertSame('results', Status::Completed->activeStepKey());
    }

    public function testStepNumber(): void
    {
        $this->assertSame(1, Status::Started->stepNumber());
        $this->assertSame(2, Status::Scope1Answered->stepNumber());
        $this->assertSame(3, Status::Scope2Answered->stepNumber());
        $this->assertSame(4, Status::Scope3Answered->stepNumber());
        $this->assertSame(5, Status::ProvidersAnswered->stepNumber());
        $this->assertSame(6, Status::EmployeesAnswered->stepNumber());
        $this->assertSame(7, Status::Completed->stepNumber());
    }
}
