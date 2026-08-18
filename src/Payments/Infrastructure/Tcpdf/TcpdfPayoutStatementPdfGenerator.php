<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Tcpdf;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutStatementLine;
use App\Payments\Domain\Model\Payout\PayoutStatementPdfGenerator;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfPayoutStatementPdfGenerator implements PayoutStatementPdfGenerator
{
    use GenericGenerator;

    public function __construct(private readonly TranslatorInterface $translator) {}

    /** @param list<PayoutStatementLine> $lines */
    public function generate(Payout $payout, Payee $payee, array $lines): string
    {
        $documentName = $this->translator->trans('Justificante de liquidación');
        $number = (string) $payout->number();

        $pdf = $this->createPDF(sprintf('%s %s', $documentName, $number));

        $this->startContentPages($pdf, $documentName);
        $pdf->AddPage();

        $this->renderSectionTitle($pdf, sprintf('%s %s', $documentName, $number));
        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML($this->buildTableHtml(
            [$this->translator->trans('Concepto'), $this->translator->trans('Valor')],
            $this->summaryRows($payout, $payee),
            ['40%', '60%'],
        ), true, false, true, false, '');

        $pdf->Ln(8);

        $this->renderSectionTitle($pdf, $this->translator->trans('Detalle de asientos'));
        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML($this->buildTableHtml(
            [
                $this->translator->trans('Fecha'),
                $this->translator->trans('Concepto'),
                $this->translator->trans('Proyecto'),
                $this->translator->trans('Lote'),
                $this->translator->trans('Toneladas'),
                $this->translator->trans('Importe'),
            ],
            $this->detailRows($lines),
            ['12%', '28%', '22%', '16%', '11%', '11%'],
        ), true, false, true, false, '');

        $path = sys_get_temp_dir() . '/' . uniqid('payout-statement-', true) . '.pdf';
        $this->savePDF($pdf, $path);

        return $path;
    }

    /** @return list<list<string>> */
    private function summaryRows(Payout $payout, Payee $payee): array
    {
        return [
            [$this->translator->trans('Liquidación'), (string) $payout->number()],
            [$this->translator->trans('Beneficiario'), $payee->legalName()],
            [$this->translator->trans('NIF / CIF'), (string) ($payee->taxId() ?? '-')],
            [$this->translator->trans('IBAN'), (string) ($payee->maskedIban() ?? '-')],
            [$this->translator->trans('Periodo'), $this->period($payout)],
            [$this->translator->trans('Importe'), $this->money($payout->payableAmount(), $payout->currency())],
            [$this->translator->trans('Referencia'), (string) ($payout->reference() ?? '-')],
            [$this->translator->trans('Estado'), $this->translator->trans($payout->status()->value, [], 'payoutStatus')],
        ];
    }

    /**
     * @param list<PayoutStatementLine> $lines
     *
     * @return list<list<string>>
     */
    private function detailRows(array $lines): array
    {
        return array_map(
            fn(PayoutStatementLine $line): array => [
                $line->occurredAt->format('d/m/Y'),
                $line->concept,
                (string) ($line->projectName ?? '-'),
                (string) ($line->purchaseReference ?? '-'),
                null !== $line->tonnes ? number_format($line->tonnes, 4, ',', '.') : '-',
                $this->money($line->amount, 'EUR'),
            ],
            $lines,
        );
    }

    private function period(Payout $payout): string
    {
        $from = $payout->periodFrom();
        $to = $payout->periodTo();

        if (!$from instanceof \DateTimeImmutable || !$to instanceof \DateTimeImmutable) {
            return '-';
        }

        return sprintf('%s - %s', $from->format('d/m/Y'), $to->format('d/m/Y'));
    }

    private function money(float $amount, string $currency): string
    {
        return sprintf('%s %s', number_format($amount, 2, ',', '.'), 'EUR' === $currency ? '€' : $currency);
    }
}
