<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Infrastructure\Remittance;

use App\Payments\Domain\Model\Payout\PayoutRemittanceLine;
use App\Payments\Infrastructure\Remittance\SepaPain001RemittanceGenerator;
use PHPUnit\Framework\TestCase;

class SepaPain001RemittanceGeneratorTest extends TestCase
{
    private const string NAMESPACE_URI = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';

    public function testItBuildsOnePaymentInformationWithOneTransactionPerPayout(): void
    {
        $xpath = $this->xpathOf($this->generate());

        $this->assertSame(1, $this->nodeCount($xpath, '//sepa:PmtInf'));
        $this->assertSame(2, $this->nodeCount($xpath, '//sepa:CdtTrfTxInf'));
        $this->assertSame('TRF', $this->text($xpath, '//sepa:PmtInf/sepa:PmtMtd'));
        $this->assertSame('SEPA', $this->text($xpath, '//sepa:PmtInf/sepa:PmtTpInf/sepa:SvcLvl/sepa:Cd'));
        $this->assertSame('SLEV', $this->text($xpath, '//sepa:PmtInf/sepa:ChrgBr'));
    }

    public function testTheControlSumMatchesTheSumOfTheTransactions(): void
    {
        $xpath = $this->xpathOf($this->generate());

        $this->assertSame('320.50', $this->text($xpath, '//sepa:GrpHdr/sepa:CtrlSum'));
        $this->assertSame('2', $this->text($xpath, '//sepa:GrpHdr/sepa:NbOfTxs'));
        $this->assertSame('320.50', $this->text($xpath, '//sepa:PmtInf/sepa:CtrlSum'));
    }

    public function testEachTransactionCarriesTheEndToEndIdAndTheConcept(): void
    {
        $xpath = $this->xpathOf($this->generate());

        $this->assertSame('2026/0001', $this->text($xpath, '(//sepa:CdtTrfTxInf)[1]/sepa:PmtId/sepa:EndToEndId'));
        $this->assertSame('280.00', $this->text($xpath, '(//sepa:CdtTrfTxInf)[1]/sepa:Amt/sepa:InstdAmt'));
        $this->assertSame('Liquidacion 2026/0001 - Hotel X', $this->text($xpath, '(//sepa:CdtTrfTxInf)[1]/sepa:RmtInf/sepa:Ustrd'));
        $this->assertSame('ES9121000418450200051332', $this->text($xpath, '(//sepa:CdtTrfTxInf)[1]/sepa:CdtrAcct/sepa:Id/sepa:IBAN'));
    }

    public function testTheDebtorIsTheConfiguredOrderingParty(): void
    {
        $xpath = $this->xpathOf($this->generate());

        $this->assertSame('Bioscore Sustainability', $this->text($xpath, '//sepa:PmtInf/sepa:Dbtr/sepa:Nm'));
        $this->assertSame('ES7620770024003102575766', $this->text($xpath, '//sepa:PmtInf/sepa:DbtrAcct/sepa:Id/sepa:IBAN'));
        $this->assertSame('CAIXESBBXXX', $this->text($xpath, '//sepa:PmtInf/sepa:DbtrAgt/sepa:FinInstnId/sepa:BIC'));
        $this->assertSame('ES12345', $this->text($xpath, '//sepa:GrpHdr/sepa:InitgPty/sepa:Id/sepa:OrgId/sepa:Othr/sepa:Id'));
    }

    public function testTheExecutionDateIsTheRequestedOne(): void
    {
        $xpath = $this->xpathOf($this->generate());

        $this->assertSame('2026-08-14', $this->text($xpath, '//sepa:PmtInf/sepa:ReqdExctnDt'));
    }

    private function generate(): string
    {
        return (new SepaPain001RemittanceGenerator(
            debtorName: 'Bioscore Sustainability',
            debtorIban: 'ES76 2077 0024 0031 0257 5766',
            debtorBic: 'CAIXESBBXXX',
            initiatingPartyId: 'ES12345',
        ))->generate(
            [
                new PayoutRemittanceLine(
                    endToEndId: '2026/0001',
                    amount: 280.0,
                    currency: 'EUR',
                    creditorName: 'Hotel X SL',
                    iban: 'ES91 2100 0418 4502 0005 1332',
                    bic: 'CAIXESBBXXX',
                    concept: 'Liquidacion 2026/0001 - Hotel X',
                ),
                new PayoutRemittanceLine(
                    endToEndId: '2026/0002',
                    amount: 40.5,
                    currency: 'EUR',
                    creditorName: 'Pozos de agua',
                    iban: 'ES9121000418450200051333',
                    bic: null,
                    concept: 'Liquidacion 2026/0002 - Pozos de agua',
                ),
            ],
            new \DateTimeImmutable('2026-08-14 10:00:00'),
        );
    }

    private function xpathOf(string $xml): \DOMXPath
    {
        $document = new \DOMDocument();

        $this->assertTrue($document->loadXML($xml));

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sepa', self::NAMESPACE_URI);

        return $xpath;
    }

    private function nodeCount(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);

        $this->assertNotFalse($nodes);

        return $nodes->length;
    }

    private function text(\DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);

        $this->assertNotFalse($nodes);
        $this->assertGreaterThan(0, $nodes->length, $query);

        $node = $nodes->item(0);

        $this->assertInstanceOf(\DOMNode::class, $node);

        return $node->textContent;
    }
}
