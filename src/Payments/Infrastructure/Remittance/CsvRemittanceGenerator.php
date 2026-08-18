<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Remittance;

use App\Payments\Domain\Model\Payout\PayoutRemittanceGenerator;
use App\Payments\Domain\Model\Payout\PayoutRemittanceLine;

class CsvRemittanceGenerator implements PayoutRemittanceGenerator
{
    public function format(): string
    {
        return 'csv';
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function contentType(): string
    {
        return 'text/csv';
    }

    /** @param list<PayoutRemittanceLine> $lines */
    public function generate(array $lines, \DateTimeImmutable $executionDate): string
    {
        $handle = fopen('php://temp', 'r+');

        if (false === $handle) {
            throw new \RuntimeException('Unable to open the remittance buffer.');
        }

        fputcsv($handle, ['referencia', 'fecha_ejecucion', 'beneficiario', 'iban', 'bic', 'importe', 'moneda', 'concepto'], ';', '"', '\\');

        foreach ($lines as $line) {
            fputcsv($handle, [
                $line->endToEndId,
                $executionDate->format('Y-m-d'),
                $line->creditorName,
                str_replace(' ', '', $line->iban),
                (string) $line->bic,
                number_format($line->amount, 2, '.', ''),
                $line->currency,
                $line->concept,
            ], ';', '"', '\\');
        }

        rewind($handle);

        $content = (string) stream_get_contents($handle);

        fclose($handle);

        return $content;
    }
}
