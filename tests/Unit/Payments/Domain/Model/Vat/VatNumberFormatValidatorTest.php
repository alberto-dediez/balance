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
            'spanish company' => ['ESB12345678', 'ES'],
            'spanish with spaces' => ['ES B-1234 5678', 'ES'],
            'german' => ['DE123456789', 'DE'],
            'french' => ['FRAB123456789', 'FR'],
            'italian' => ['IT12345678901', 'IT'],
            'dutch' => ['NL123456789B01', 'NL'],
            'german without prefix' => ['123456789', 'DE'],
            'greek with its EL prefix' => ['EL123456789', 'GR'],
            'greek with the ISO prefix' => ['GR123456789', 'GR'],
            'greek without prefix' => ['123456789', 'GR'],
        ];
    }

    /** @return array<string, array{?string, string}> */
    public static function invalidNumbers(): array
    {
        return [
            'null' => [null, 'ES'],
            'empty' => ['', 'ES'],
            'too short' => ['DE123', 'DE'],
            'wrong country prefix' => ['FR123456789', 'DE'],
            'too long without prefix' => ['1234567890', 'DE'],
            'unknown country' => ['US123456789', 'US'],
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
