<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Shortcut;

use App\Shared\Application\Search\GlobalSearchService;
use App\Shared\Application\Shortcut\PlatformShortcutProvider;
use App\Shared\Application\Shortcut\Shortcut;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\Search\FakeSearchProvider;
use PHPUnit\Framework\TestCase;

class PlatformShortcutProviderTest extends TestCase
{
    public function testOffersTheSearchAndTheHelp(): void
    {
        $sut = new PlatformShortcutProvider(new GlobalSearchService([FakeSearchProvider::withResults('users', 150)]));

        $shortcuts = $sut->shortcuts(UserMother::adminUser());

        $this->assertSame(['ctrl+k', 'ctrl+enter', 'ctrl+f1'], $this->ids($shortcuts));
        $this->assertSame(PlatformShortcutProvider::SEARCH_EVENT, $shortcuts[0]->event);
        $this->assertTrue($shortcuts[1]->submit);
        $this->assertSame(PlatformShortcutProvider::HELP_EVENT, $shortcuts[2]->event);
    }

    public function testOnlyOffersTheHelpWithoutSearch(): void
    {
        $sut = new PlatformShortcutProvider(new GlobalSearchService([]));

        $shortcuts = $sut->shortcuts(UserMother::registeredUser());

        $this->assertSame(['ctrl+enter', 'ctrl+f1'], $this->ids($shortcuts));
        $this->assertSame('help', $shortcuts[1]->event);
    }

    public function testIsAlwaysAvailable(): void
    {
        $sut = new PlatformShortcutProvider(new GlobalSearchService([]));

        $this->assertTrue($sut->isAvailableTo(UserMother::registeredUser()));
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
    }

    /**
     * @param list<Shortcut> $shortcuts
     *
     * @return list<string>
     */
    private function ids(array $shortcuts): array
    {
        return array_map(fn(Shortcut $shortcut): string => $shortcut->combination->id(), $shortcuts);
    }
}
