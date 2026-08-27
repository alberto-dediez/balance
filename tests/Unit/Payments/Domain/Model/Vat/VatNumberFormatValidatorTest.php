<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Vat;

use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use PHPUnit\Framework\TestCase;

class VatNumberFormatValidatorTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function validNumbers(): array
    {
        return [
            'spanish company' => ['ESB12345678', 'ESP'],
            'spanish with spaces' => ['ES B-1234 5678', 'ESP'],
            'german' => ['DE123456789', 'DEU'],
            'french' => ['FRAB123456789', 'FRA'],
            'italian' => ['IT12345678901', 'ITA'],
            'dutch' => ['NL123456789B01', 'NLD'],
            'german without prefix' => ['123456789', 'DEU'],
            'greek with its EL prefix' => ['EL123456789', 'GRC'],
            'greek with the ISO prefix' => ['GRC123456789', 'GRC'],
            'greek without prefix' => ['123456789', 'GRC'],
        ];
    }

    /** @return array<string, array{?string, string}> */
    public static function invalidNumbers(): array
    {
        return [
            'null' => [null, 'ESP'],
            'empty' => ['', 'ESP'],
            'too short' => ['DE123', 'DEU'],
            'wrong country prefix' => ['FR123456789', 'DEU'],
            'too long without prefix' => ['1234567890', 'DEU'],
            'unknown country' => ['US123456789', 'USA'],
            'alpha-2 country code' => ['ESB12345678', 'ES'],
        ];
    }

    /** @dataProvider validNumbers */
    public function testAcceptsWellFormedNumbers(string $vatNumber, string $country): void
    {
        $this->assertTrue((new VatNumberFormatValidator())->isValid($vatNumber, $country));
    }

    /** @dataProvider invalidNumbers */
    public function testRejectsMalformedNumbers(?string $vatNumber, string $country): void
    {
        $this->assertFalse((new VatNumberFormatValidator())->isValid($vatNumber, $country));
    }

    public function testNormalizesSeparatorsAndCase(): void
    {
        $this->assertSame('ESB12345678', (new VatNumberFormatValidator())->normalize('es b-1234.5678'));
    }
}
