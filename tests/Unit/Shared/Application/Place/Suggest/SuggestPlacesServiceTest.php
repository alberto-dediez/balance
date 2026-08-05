<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Place\Suggest;

use App\Shared\Application\Place\Suggest\SuggestPlacesRequest;
use App\Shared\Application\Place\Suggest\SuggestPlacesService;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class SuggestPlacesServiceTest extends TestCase
{
    private InMemoryPlaceFinder $placeFinder;

    protected function setUp(): void
    {
        $this->placeFinder = new InMemoryPlaceFinder();
        $this->placeFinder->add('madrid', 'Madrid, España', 40.4168, -3.7038);
    }

    public function testSuggestsPlacesMatchingTheQuery(): void
    {
        $response = ($this->service())(new SuggestPlacesRequest('Madrid', PlaceKind::City));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('madrid', $response->suggestions[0]->reference);
    }

    public function testDoesNotSuggestAnythingForQueriesShorterThanTheMinimumLength(): void
    {
        $response = ($this->service())(new SuggestPlacesRequest('Ma'));

        $this->assertSame([], $response->suggestions);
    }

    public function testTrimsTheQueryBeforeMeasuringItsLength(): void
    {
        $response = ($this->service())(new SuggestPlacesRequest('  Ma  '));

        $this->assertSame([], $response->suggestions);
    }

    public function testTrimsTheQueryBeforeSuggesting(): void
    {
        $response = ($this->service())(new SuggestPlacesRequest('  Madrid  '));

        $this->assertCount(1, $response->suggestions);
    }

    public function testAQueryOfExactlyTheMinimumLengthIsSuggested(): void
    {
        $response = ($this->service())(new SuggestPlacesRequest('Mad'));

        $this->assertCount(1, $response->suggestions);
    }

    public function testMultibyteCharactersCountAsASingleCharacter(): void
    {
        $this->placeFinder->add('avila', 'Ávila, España', 40.6565, -4.6818);

        $response = ($this->service())(new SuggestPlacesRequest('Ávi'));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('avila', $response->suggestions[0]->reference);
    }

    private function service(): SuggestPlacesService
    {
        return new SuggestPlacesService($this->placeFinder);
    }
}
