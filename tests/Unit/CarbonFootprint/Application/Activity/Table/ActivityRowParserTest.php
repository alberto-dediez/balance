<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Application\Activity\Table\ActivityRowParser;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use PHPUnit\Framework\TestCase;

class ActivityRowParserTest extends TestCase
{
    private ActivityRowParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ActivityRowParser();
    }

    public function testParsesValidRowsWithCondition(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '10'],
            ['itemId' => 'material-2', 'condition' => 'recycled', 'unit' => 'ud', 'quantity' => '2.5'],
        ], ActivityType::Material, withCondition: true);

        $this->assertCount(2, $rows);
        $this->assertSame('material-1', $rows[0]->itemId);
        $this->assertSame(ItemCondition::New, $rows[0]->condition);
        $this->assertSame(10.0, $rows[0]->quantity->value());
        $this->assertNull($rows[0]->secondaryItemId);
    }

    public function testFallsBackToNewConditionWhenConditionIsMissing(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '10'],
        ], ActivityType::Material, withCondition: true);

        $this->assertCount(1, $rows);
        $this->assertSame(ItemCondition::New, $rows[0]->condition);
    }

    public function testForcesNewConditionWhenWithConditionIsFalse(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'combustible-1', 'unit' => 'l', 'quantity' => '5'],
        ], ActivityType::Combustible, withCondition: false);

        $this->assertCount(1, $rows);
        $this->assertSame(ItemCondition::New, $rows[0]->condition);
    }

    public function testParsesSecondaryItemIdWhenEnabled(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100'],
        ], ActivityType::Electricity, hasSecondaryItemId: true);

        $this->assertCount(1, $rows);
        $this->assertSame('marketer-1', $rows[0]->itemId);
        $this->assertSame('gdo-1', $rows[0]->secondaryItemId);
    }

    public function testSkipsRowWithEmptySecondaryItemIdWhenRequired(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'marketer-1', 'itemId2' => '', 'unit' => 'kWh', 'quantity' => '100'],
        ], ActivityType::Electricity, hasSecondaryItemId: true);

        $this->assertSame([], $rows);
    }

    public function testAcceptsCommaAsDecimalSeparator(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '10,5'],
        ], ActivityType::Material, withCondition: true);

        $this->assertCount(1, $rows);
        $this->assertSame(10.5, $rows[0]->quantity->value());
    }

    public function testSkipsRowsWithEmptyItemId(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => '', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '10'],
        ], ActivityType::Material, withCondition: true);

        $this->assertSame([], $rows);
    }

    public function testSkipsRowsWithInvalidUnit(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'condition' => 'new', 'unit' => 'invalid', 'quantity' => '10'],
        ], ActivityType::Material, withCondition: true);

        $this->assertSame([], $rows);
    }

    public function testSkipsRowsWithNonPositiveQuantity(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '0'],
            ['itemId' => 'material-2', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '-5'],
        ], ActivityType::Material, withCondition: true);

        $this->assertSame([], $rows);
    }

    public function testSkipsNonArrayRows(): void
    {
        $rows = $this->parser->parse(['not-an-array', ''], ActivityType::Material, withCondition: true);

        $this->assertSame([], $rows);
    }

    public function testPreservesActivityIdWhenProvided(): void
    {
        $rows = $this->parser->parse([
            ['itemId' => 'material-1', 'condition' => 'new', 'unit' => 'kg', 'quantity' => '10', 'activityId' => 'activity-1'],
        ], ActivityType::Material, withCondition: true);

        $this->assertCount(1, $rows);
        $this->assertNotNull($rows[0]->activityId);
        $this->assertSame('activity-1', (string) $rows[0]->activityId);
    }
}
