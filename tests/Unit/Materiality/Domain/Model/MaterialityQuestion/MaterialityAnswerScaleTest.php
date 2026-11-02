<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use PHPUnit\Framework\TestCase;

final class MaterialityAnswerScaleTest extends TestCase
{
    public function testLabelKeyOfLevel(): void
    {
        $this->assertSame('level_1', MaterialityAnswerScale::Level->labelKey(1));
        $this->assertSame('level_2', MaterialityAnswerScale::Level->labelKey(2));
        $this->assertSame('level_3', MaterialityAnswerScale::Level->labelKey(3));
    }

    public function testLabelKeyOfYesNo(): void
    {
        $this->assertSame('yes_no_1', MaterialityAnswerScale::YesNo->labelKey(1));
        $this->assertSame('yes_no_3', MaterialityAnswerScale::YesNo->labelKey(3));
    }

    public function testLabelKeyOfAValueOutsideTheScaleThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MaterialityAnswerScale::YesNo->labelKey(2);
    }
}
