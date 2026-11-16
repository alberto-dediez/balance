<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Shortcut;

use App\Shared\Application\Shortcut\KeyCombination;
use App\Shared\Application\Shortcut\Shortcut;
use PHPUnit\Framework\TestCase;

class ShortcutTest extends TestCase
{
    public function testToRouteHasNoEvent(): void
    {
        $shortcut = Shortcut::toRoute(KeyCombination::key('t'), 'Crear tarea', 'solar:clipboard-list-line-duotone', 100, 'admin_tasks_register', ['back' => 'list']);

        $this->assertSame('admin_tasks_register', $shortcut->route);
        $this->assertSame(['back' => 'list'], $shortcut->routeParameters);
        $this->assertNull($shortcut->event);
    }

    public function testDispatchingHasNoRoute(): void
    {
        $shortcut = Shortcut::dispatching(KeyCombination::ctrl('k'), 'Buscar', 'solar:magnifer-line-duotone', 0, 'global-search');

        $this->assertSame('global-search', $shortcut->event);
        $this->assertNull($shortcut->route);
        $this->assertSame([], $shortcut->routeParameters);
    }

    public function testDispatchingCanActWithinAModal(): void
    {
        $shortcut = Shortcut::dispatching(KeyCombination::shift('r'), 'Fijar respuesta', 'solar:pin-line-duotone', 220, 'feature-reply-dock', withinModal: true);

        $this->assertTrue($shortcut->withinModal);
        $this->assertSame('feature-reply-dock', $shortcut->event);
    }

    public function testDispatchingDoesNotActWithinAModalByDefault(): void
    {
        $shortcut = Shortcut::dispatching(KeyCombination::ctrl('k'), 'Buscar', 'solar:magnifer-line-duotone', 0, 'global-search');

        $this->assertFalse($shortcut->withinModal);
    }

    public function testSubmittingHasNoRouteNorEvent(): void
    {
        $shortcut = Shortcut::submitting(KeyCombination::ctrl('enter'), 'Enviar formulario', 'solar:plain-line-duotone', 10);

        $this->assertTrue($shortcut->submit);
        $this->assertNull($shortcut->route);
        $this->assertNull($shortcut->event);
    }

    public function testCannotSubmitAndHaveARoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::ctrl('enter'), 'Enviar formulario', 'solar:plain-line-duotone', 10, route: 'admin_tasks_register', submit: true);
    }

    public function testCannotSubmitAndHaveAnEvent(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::ctrl('enter'), 'Enviar formulario', 'solar:plain-line-duotone', 10, event: 'help', submit: true);
    }

    public function testCannotHaveARouteAndAnEvent(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::key('t'), 'Crear tarea', 'solar:clipboard-list-line-duotone', 100, route: 'admin_tasks_register', event: 'help');
    }

    public function testNeedsARouteOrAnEvent(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::key('t'), 'Crear tarea', 'solar:clipboard-list-line-duotone', 100);
    }

    public function testCannotActWithinAModalWithARoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::key('t'), 'Crear tarea', 'solar:clipboard-list-line-duotone', 100, route: 'admin_tasks_register', withinModal: true);
    }

    public function testCannotActWithinAModalWhenSubmitting(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Shortcut(KeyCombination::ctrl('enter'), 'Enviar formulario', 'solar:plain-line-duotone', 10, submit: true, withinModal: true);
    }
}
