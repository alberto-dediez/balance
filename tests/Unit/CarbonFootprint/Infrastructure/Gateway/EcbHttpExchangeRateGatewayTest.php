<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Gateway;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSourceUnavailableException;
use App\CarbonFootprint\Infrastructure\Gateway\EcbHttpExchangeRateGateway;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class EcbHttpExchangeRateGatewayTest extends TestCase
{
    private const string URL = 'http://ecb.test/eurofxref-daily.xml';

    public function testParsesTheDailyFeed(): void
    {
        $feed = $this->createSUT(new MockResponse($this->xml(<<<'XML'
            <Cube currency='USD' rate='1.1195'/>
            <Cube currency='JPY' rate='171.42'/>
            <Cube currency='GBP' rate='0.8412'/>
            XML)))->fetch();

        $this->assertEquals(new \DateTimeImmutable('2026-10-14'), $feed->date);
        $this->assertCount(3, $feed->entries);
        $this->assertSame('USD', $feed->entries[0]->currency);
        $this->assertSame('1.1195', $feed->entries[0]->rate);
        $this->assertSame('GBP', $feed->entries[2]->currency);
        $this->assertSame('0.8412', $feed->entries[2]->rate);
    }

    public function testSkipsEntriesWithoutRate(): void
    {
        $feed = $this->createSUT(new MockResponse($this->xml(<<<'XML'
            <Cube currency='USD' rate='1.1195'/>
            <Cube currency='JPY'/>
            XML)))->fetch();

        $this->assertCount(1, $feed->entries);
        $this->assertSame('USD', $feed->entries[0]->currency);
    }

    public function testThrowsWhenTheStatusIsNotOk(): void
    {
        $this->expectException(ExchangeRateSourceUnavailableException::class);

        $this->createSUT(new MockResponse('', ['http_code' => 500]))->fetch();
    }

    public function testThrowsWhenTheTransportFails(): void
    {
        $this->expectException(ExchangeRateSourceUnavailableException::class);

        $this->createSUT(static fn() => throw new TransportException('Connection refused'))->fetch();
    }

    public function testThrowsWhenTheTimeCubeIsMissing(): void
    {
        $this->expectException(ExchangeRateSourceUnavailableException::class);
        $this->expectExceptionMessage('Missing time cube');

        $this->createSUT(new MockResponse(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
                <gesmes:subject>Reference rates</gesmes:subject>
                <Cube>
                    <Cube>
                        <Cube currency='USD' rate='1.1195'/>
                    </Cube>
                </Cube>
            </gesmes:Envelope>
            XML))->fetch();
    }

    public function testThrowsWhenTheContentIsNotXml(): void
    {
        $this->expectException(ExchangeRateSourceUnavailableException::class);
        $this->expectExceptionMessage('Invalid XML');

        $this->createSUT(new MockResponse('<html><body>Maintenance'))->fetch();
    }

    private function xml(string $cubes): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
                <gesmes:subject>Reference rates</gesmes:subject>
                <gesmes:Sender>
                    <gesmes:name>European Central Bank</gesmes:name>
                </gesmes:Sender>
                <Cube>
                    <Cube time='2026-10-14'>
                        {$cubes}
                    </Cube>
                </Cube>
            </gesmes:Envelope>
            XML;
    }

    private function createSUT(MockResponse|\Closure $response): EcbHttpExchangeRateGateway
    {
        return new EcbHttpExchangeRateGateway(new MockHttpClient($response), self::URL, new NullLogger());
    }
}
