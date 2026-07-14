<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model;

use App\CarbonFootprint\Domain\Model\Emissions;
use PHPUnit\Framework\TestCase;

class EmissionsTest extends TestCase
{
    public function testAddSumsFieldByField(): void
    {
        $a = new Emissions('1.5', '2.5', '0.5', '0.1');
        $b = new Emissions('2.5', '1.5', '0.3', '0.2');

        $result = $a->add($b);

        $this->assertSame('4', $result->co2eq());
        $this->assertSame('4', $result->co2());
        $this->assertSame('0.8', $result->ch4());
        $this->assertSame('0.3', $result->n2o());
    }

    public function testAddWithZeroReturnsSameValues(): void
    {
        $a = new Emissions('1.5', '2.5', '0.5', '0.1');
        $zero = new Emissions();

        $result = $a->add($zero);

        $this->assertSame('1.5', $result->co2eq());
        $this->assertSame('2.5', $result->co2());
        $this->assertSame('0.5', $result->ch4());
        $this->assertSame('0.1', $result->n2o());
    }

    public function testDividedByDividesFieldByField(): void
    {
        $emissions = new Emissions('10', '20', '4', '2');

        $result = $emissions->dividedBy(2);

        $this->assertSame('5', $result->co2eq());
        $this->assertSame('10', $result->co2());
        $this->assertSame('2', $result->ch4());
        $this->assertSame('1', $result->n2o());
    }

    public function testDividedByZeroThrowsException(): void
    {
        $emissions = new Emissions('10', '20', '4', '2');

        $this->expectException(\InvalidArgumentException::class);
        $emissions->dividedBy(0);
    }

    public function testDividedByNegativeThrowsException(): void
    {
        $emissions = new Emissions('10', '20', '4', '2');

        $this->expectException(\InvalidArgumentException::class);
        $emissions->dividedBy(-1);
    }

    public function testAddIsImmutable(): void
    {
        $a = new Emissions('1', '1', '1', '1');
        $b = new Emissions('1', '1', '1', '1');

        $a->add($b);

        $this->assertSame('1', $a->co2eq());
        $this->assertSame('1', $b->co2eq());
    }

    public function testDividedByIsImmutable(): void
    {
        $emissions = new Emissions('10', '10', '10', '10');

        $emissions->dividedBy(2);

        $this->assertSame('10', $emissions->co2eq());
    }
}
