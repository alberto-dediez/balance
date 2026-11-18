<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Shortcut;

use App\Shared\Application\Shortcut\DuplicatedShortcutException;
use App\Shared\Application\Shortcut\KeyCombination;
use App\Shared\Application\Shortcut\Shortcut;
use App\Shared\Application\Shortcut\ShortcutLister;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ShortcutListerTest extends TestCase
{
    public function testOrdersByPositionAndThenByLabel(): void
    {
        $sut = new ShortcutLister([
            FakeShortcutProvider::withShortcuts(
                true,
                $this->shortcut('h', 'Help', 1000),
                $this->shortcut('b', 'Beta', 100),
                $this->shortcut('a', 'Alpha', 100),
                $this->shortcut('s', 'Search', 0),
            ),
        ]);

        $this->assertSame(['Search', 'Alpha', 'Beta', 'Help'], $this->labels($sut->shortcutsFor(UserMother::adminUser())));
    }

    public function testOmitsTheProvidersNotAvailableToTheUser(): void
    {
        $unavailable = FakeShortcutProvider::withShortcuts(false, $this->shortcut('f', 'Features', 200));
        $sut = new ShortcutLister([
            $unavailable,
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('t', 'Tasks', 100)),
        ]);

        $this->assertSame(['Tasks'], $this->labels($sut->shortcutsFor(UserMother::ownerUser())));
        $this->assertSame(0, $unavailable->calls);
    }

    public function testConcatenatesSeveralProviders(): void
    {
        $sut = new ShortcutLister([
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('f', 'Features', 200)),
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('t', 'Tasks', 100), $this->shortcut('u', 'Users', 300)),
        ]);

        $this->assertSame(['Tasks', 'Features', 'Users'], $this->labels($sut->shortcutsFor(UserMother::adminUser())));
    }

    public function testFailsWithTwoAvailableShortcutsWithTheSameCombination(): void
    {
        $sut = new ShortcutLister([
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('t', 'Tasks', 100)),
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('T', 'Tickets', 150)),
        ]);

        $this->expectException(DuplicatedShortcutException::class);
        $this->expectExceptionMessage('The shortcut T is declared by "Tasks" and "Tickets"');

        $sut->shortcutsFor(UserMother::adminUser());
    }

    public function testADuplicateOfAnUnavailableProviderDoesNotFail(): void
    {
        $sut = new ShortcutLister([
            FakeShortcutProvider::withShortcuts(true, $this->shortcut('t', 'Tasks', 100)),
            FakeShortcutProvider::withShortcuts(false, $this->shortcut('t', 'Tickets', 150)),
        ]);

        $this->assertSame(['Tasks'], $this->labels($sut->shortcutsFor(UserMother::adminUser())));
    }

    public function testASingleKeyAndItsCtrlCombinationAreNotDuplicated(): void
    {
        $sut = new ShortcutLister([
            FakeShortcutProvider::withShortcuts(
                true,
                $this->shortcut('t', 'Tasks', 100),
                Shortcut::dispatching(KeyCombination::ctrl('t'), 'Tabs', 'solar:magnifer-line-duotone', 150, 'tabs'),
            ),
        ]);

        $this->assertSame(['Tasks', 'Tabs'], $this->labels($sut->shortcutsFor(UserMother::adminUser())));
    }

    public function testWithoutProvidersReturnsNothing(): void
    {
        $this->assertSame([], (new ShortcutLister([]))->shortcutsFor(UserMother::adminUser()));
    }

    private function shortcut(string $key, string $label, int $position): Shortcut
    {
        return Shortcut::toRoute(KeyCombination::key($key), $label, 'solar:magnifer-line-duotone', $position, sprintf('%s_route', $key));
    }

    /**
     * @param list<Shortcut> $shortcuts
     *
     * @return list<string>
     */
    private function labels(array $shortcuts): array
    {
        return array_map(fn(Shortcut $shortcut): string => $shortcut->label, $shortcuts);
    }
}
