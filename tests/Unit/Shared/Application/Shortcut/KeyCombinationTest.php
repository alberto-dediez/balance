<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Shortcut;

use App\Shared\Application\Shortcut\KeyCombination;
use PHPUnit\Framework\TestCase;

class KeyCombinationTest extends TestCase
{
    public function testASingleKeyIsLowercasedAndHasNoModifier(): void
    {
        $combination = KeyCombination::key('T');

        $this->assertSame('t', $combination->id());
        $this->assertSame('T', $combination->label());
        $this->assertFalse($combination->hasModifier());
        $this->assertFalse($combination->isGlobal());
    }

    public function testACtrlCombination(): void
    {
        $combination = KeyCombination::ctrl('k');

        $this->assertSame('ctrl+k', $combination->id());
        $this->assertSame('Ctrl+K', $combination->label());
        $this->assertTrue($combination->hasModifier());
        $this->assertTrue($combination->isGlobal());
    }

    public function testAShiftCombinationIsNotGlobal(): void
    {
        $combination = KeyCombination::shift('f');

        $this->assertSame('shift+f', $combination->id());
        $this->assertSame('Shift+F', $combination->label());
        $this->assertTrue($combination->hasModifier());
        $this->assertFalse($combination->isGlobal());
    }

    public function testAnAltCombinationIsGlobal(): void
    {
        $this->assertTrue((new KeyCombination('t', alt: true))->isGlobal());
    }

    public function testAFunctionKey(): void
    {
        $combination = KeyCombination::ctrl('F1');

        $this->assertSame('ctrl+f1', $combination->id());
        $this->assertSame('Ctrl+F1', $combination->label());
    }

    public function testEnterIsASupportedNamedKey(): void
    {
        $combination = KeyCombination::ctrl('Enter');

        $this->assertSame('ctrl+enter', $combination->id());
        $this->assertSame('Ctrl+Enter', $combination->label());
        $this->assertTrue($combination->isGlobal());
    }

    public function testEscapeIsNotValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KeyCombination('escape');
    }

    public function testTheModifiersFollowTheirOrder(): void
    {
        $combination = new KeyCombination('t', ctrl: true, shift: true);

        $this->assertSame('ctrl+shift+t', $combination->id());
        $this->assertSame('Ctrl+Shift+T', $combination->label());
        $this->assertTrue($combination->isGlobal());
    }

    public function testAnEmptyKeyIsNotValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KeyCombination::key('');
    }

    public function testSeveralCharactersAreNotValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KeyCombination::key('ab');
    }

    public function testF12IsValid(): void
    {
        $this->assertSame('f12', KeyCombination::key('f12')->id());
    }

    public function testF13IsNotValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KeyCombination::key('f13');
    }
}
