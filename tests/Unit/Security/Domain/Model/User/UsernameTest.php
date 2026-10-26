<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\InvalidUsernameException;
use App\Security\Domain\Model\User\Username;
use PHPUnit\Framework\TestCase;

class UsernameTest extends TestCase
{
    public function testAcceptsAnEmail(): void
    {
        $username = new Username('an@email.com');

        $this->assertSame('an@email.com', $username->value);
        $this->assertSame('an@email.com', (string) $username);
    }

    public function testAcceptsAmpersandAndQuotes(): void
    {
        $username = new Username('o\'brien&co"x@email.com');

        $this->assertSame('o\'brien&co"x@email.com', $username->value);
    }

    /**
     * @dataProvider htmlUsernames
     */
    public function testRejectsHtml(string $value): void
    {
        $this->expectException(InvalidUsernameException::class);

        new Username($value);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function htmlUsernames(): array
    {
        return [
            'tag' => ['<b>pepe</b>'],
            'img' => ['<img src=x onerror=alert(1)>'],
            'less than' => ['a<b'],
            'greater than' => ['a>b'],
        ];
    }
}
