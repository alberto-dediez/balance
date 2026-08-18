<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Remittance;

use App\Payments\Domain\Model\Payout\PayoutRemittanceGenerator;
use App\Payments\Domain\Model\Payout\PayoutRemittanceLine;

class SepaPain001RemittanceGenerator implements PayoutRemittanceGenerator
{
    private const string NAMESPACE_URI = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';

    public function __construct(
        private readonly string $debtorName,
        private readonly string $debtorIban,
        private readonly ?string $debtorBic = null,
        private readonly ?string $initiatingPartyId = null,
    ) {}

    public function format(): string
    {
        return 'sepa';
    }

    public function extension(): string
    {
        return 'xml';
    }

    public function contentType(): string
    {
        return 'application/xml';
    }

    /** @param list<PayoutRemittanceLine> $lines */
    public function generate(array $lines, \DateTimeImmutable $executionDate): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElementNS(self::NAMESPACE_URI, 'Document');
        $document->appendChild($root);

        $initiation = $this->child($document, $root, 'CstmrCdtTrfInitn');

        $messageId = sprintf('BIOSCORE-%s', $executionDate->format('YmdHis'));
        $controlSum = $this->controlSum($lines);

        $this->appendGroupHeader($document, $initiation, $messageId, count($lines), $controlSum, $executionDate);
        $this->appendPaymentInformation($document, $initiation, $messageId, $lines, $controlSum, $executionDate);

        return (string) $document->saveXML();
    }

    private function appendGroupHeader(
        \DOMDocument $document,
        \DOMElement $parent,
        string $messageId,
        int $transactions,
        float $controlSum,
        \DateTimeImmutable $executionDate,
    ): void {
        $header = $this->child($document, $parent, 'GrpHdr');

        $this->child($document, $header, 'MsgId', $messageId);
        $this->child($document, $header, 'CreDtTm', $executionDate->format('Y-m-d\TH:i:s'));
        $this->child($document, $header, 'NbOfTxs', (string) $transactions);
        $this->child($document, $header, 'CtrlSum', $this->amount($controlSum));

        $initiatingParty = $this->child($document, $header, 'InitgPty');
        $this->child($document, $initiatingParty, 'Nm', $this->debtorName);

        if (null !== $this->initiatingPartyId && '' !== $this->initiatingPartyId) {
            $id = $this->child($document, $initiatingParty, 'Id');
            $organisation = $this->child($document, $id, 'OrgId');
            $other = $this->child($document, $organisation, 'Othr');
            $this->child($document, $other, 'Id', $this->initiatingPartyId);
        }
    }

    /** @param list<PayoutRemittanceLine> $lines */
    private function appendPaymentInformation(
        \DOMDocument $document,
        \DOMElement $parent,
        string $messageId,
        array $lines,
        float $controlSum,
        \DateTimeImmutable $executionDate,
    ): void {
        $payment = $this->child($document, $parent, 'PmtInf');

        $this->child($document, $payment, 'PmtInfId', $messageId);
        $this->child($document, $payment, 'PmtMtd', 'TRF');
        $this->child($document, $payment, 'BtchBookg', 'true');
        $this->child($document, $payment, 'NbOfTxs', (string) count($lines));
        $this->child($document, $payment, 'CtrlSum', $this->amount($controlSum));

        $typeInformation = $this->child($document, $payment, 'PmtTpInf');
        $serviceLevel = $this->child($document, $typeInformation, 'SvcLvl');
        $this->child($document, $serviceLevel, 'Cd', 'SEPA');

        $this->child($document, $payment, 'ReqdExctnDt', $executionDate->format('Y-m-d'));

        $debtor = $this->child($document, $payment, 'Dbtr');
        $this->child($document, $debtor, 'Nm', $this->debtorName);

        $debtorAccount = $this->child($document, $payment, 'DbtrAcct');
        $debtorAccountId = $this->child($document, $debtorAccount, 'Id');
        $this->child($document, $debtorAccountId, 'IBAN', $this->compact($this->debtorIban));

        if (null !== $this->debtorBic && '' !== $this->debtorBic) {
            $debtorAgent = $this->child($document, $payment, 'DbtrAgt');
            $debtorInstitution = $this->child($document, $debtorAgent, 'FinInstnId');
            $this->child($document, $debtorInstitution, 'BIC', $this->debtorBic);
        }

        $this->child($document, $payment, 'ChrgBr', 'SLEV');

        foreach ($lines as $line) {
            $this->appendTransaction($document, $payment, $line);
        }
    }

    private function appendTransaction(\DOMDocument $document, \DOMElement $parent, PayoutRemittanceLine $line): void
    {
        $transaction = $this->child($document, $parent, 'CdtTrfTxInf');

        $paymentId = $this->child($document, $transaction, 'PmtId');
        $this->child($document, $paymentId, 'EndToEndId', $line->endToEndId);

        $amount = $this->child($document, $transaction, 'Amt');
        $instructed = $this->child($document, $amount, 'InstdAmt', $this->amount($line->amount));
        $instructed->setAttribute('Ccy', $line->currency);

        if (null !== $line->bic && '' !== $line->bic) {
            $creditorAgent = $this->child($document, $transaction, 'CdtrAgt');
            $creditorInstitution = $this->child($document, $creditorAgent, 'FinInstnId');
            $this->child($document, $creditorInstitution, 'BIC', $line->bic);
        }

        $creditor = $this->child($document, $transaction, 'Cdtr');
        $this->child($document, $creditor, 'Nm', $line->creditorName);

        $creditorAccount = $this->child($document, $transaction, 'CdtrAcct');
        $creditorAccountId = $this->child($document, $creditorAccount, 'Id');
        $this->child($document, $creditorAccountId, 'IBAN', $this->compact($line->iban));

        $remittance = $this->child($document, $transaction, 'RmtInf');
        $this->child($document, $remittance, 'Ustrd', $line->concept);
    }

    private function child(\DOMDocument $document, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $element = null !== $value
            ? $document->createElementNS(self::NAMESPACE_URI, $name, htmlspecialchars($value, ENT_XML1))
            : $document->createElementNS(self::NAMESPACE_URI, $name);

        $parent->appendChild($element);

        return $element;
    }

    /** @param list<PayoutRemittanceLine> $lines */
    private function controlSum(array $lines): float
    {
        return round(array_sum(array_map(static fn(PayoutRemittanceLine $line): float => $line->amount, $lines)), 2);
    }

    private function amount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function compact(string $iban): string
    {
        return str_replace(' ', '', $iban);
    }
}
