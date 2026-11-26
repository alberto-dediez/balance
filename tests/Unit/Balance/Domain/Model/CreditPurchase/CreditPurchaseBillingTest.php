<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseBilling;
use PHPUnit\Framework\TestCase;

class CreditPurchaseBillingTest extends TestCase
{
    public function testItIsBuiltWithValidData(): void
    {
        $billing = new CreditPurchaseBilling(
            legalName: ' Mi Empresa SL ',
            taxId: ' B12345678 ',
            street: ' Calle Mayor 1 ',
            postalCode: ' 28001 ',
            city: ' Madrid ',
            country: ' esp ',
            email: ' facturas@example.com ',
        );

        $this->assertSame('Mi Empresa SL', $billing->legalName());
        $this->assertSame('B12345678', $billing->taxId());
        $this->assertSame('Calle Mayor 1', $billing->street());
        $this->assertSame('28001', $billing->postalCode());
        $this->assertSame('Madrid', $billing->city());
        $this->assertSame('ESP', $billing->country());
        $this->assertSame('facturas@example.com', $billing->email());
    }

    /**
     * @param array<string, string> $data
     *
     * @dataProvider emptyFieldProvider
     */
    public function testItThrowsWithAnEmptyField(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CreditPurchaseBillingMother::from(...$data);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function emptyFieldProvider(): iterable
    {
        yield 'legalName' => [['legalName' => ' ']];
        yield 'taxId' => [['taxId' => '']];
        yield 'street' => [['street' => '']];
        yield 'postalCode' => [['postalCode' => '']];
        yield 'city' => [['city' => '']];
        yield 'country' => [['country' => '']];
        yield 'email' => [['email' => '']];
    }

    public function testItThrowsWithAMalformedEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CreditPurchaseBillingMother::from(email: 'not-an-email');
    }
}
