<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Infrastructure\Remittance;

use App\Payments\Domain\Model\Payout\PayoutRemittanceLine;
use App\Payments\Infrastructure\Remittance\CsvRemittanceGenerator;
use PHPUnit\Framework\TestCase;

class CsvRemittanceGeneratorTest extends TestCase
{
    public function testItWritesOneRowPerPayoutWithACompactIban(): void
    {
        $csv = (new CsvRemittanceGenerator())->generate(
            [
                new PayoutRemittanceLine(
                    endToEndId: '2026/0001',
                    amount: 280.0,
                    currency: 'EUR',
                    creditorName: 'Hotel X SL',
                    iban: 'ES91 2100 0418 4502 0005 1332',
                    bic: 'CAIXESBBXXX',
                    concept: 'Liquidacion 2026/0001',
                ),
            ],
            new \DateTimeImmutable('2026-08-14'),
        );

        $rows = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $rows);
        $this->assertStringContainsString('referencia;fecha_ejecucion', $rows[0]);
        $this->assertStringContainsString('2026/0001;2026-08-14;"Hotel X SL";ES9121000418450200051332;CAIXESBBXXX;280.00;EUR', $rows[1]);
    }
}
