<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Place;

use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Infrastructure\UI\Web\Place\PlaceSuggestionsPresenter;
use PHPUnit\Framework\TestCase;

class PlaceSuggestionsPresenterTest extends TestCase
{
    public function testUsesTheReferenceAsValueAndTheLabelAsLabel(): void
    {
        $choices = PlaceSuggestionsPresenter::toChoices([new PlaceSuggestion('madrid', 'Madrid')]);

        $this->assertSame([['value' => 'madrid', 'label' => 'Madrid']], $choices);
    }

    public function testAppendsTheSecondaryLabelToTheLabel(): void
    {
        $choices = PlaceSuggestionsPresenter::toChoices([new PlaceSuggestion('madrid', 'Madrid', 'España')]);

        $this->assertSame('Madrid, España', $choices[0]['label']);
    }

    public function testAnEmptySecondaryLabelIsNotAppended(): void
    {
        $choices = PlaceSuggestionsPresenter::toChoices([new PlaceSuggestion('madrid', 'Madrid', '')]);

        $this->assertSame('Madrid', $choices[0]['label']);
    }

    public function testMapsEverySuggestionPreservingTheirOrder(): void
    {
        $choices = PlaceSuggestionsPresenter::toChoices([
            new PlaceSuggestion('madrid', 'Madrid', 'España'),
            new PlaceSuggestion('lisboa', 'Lisboa'),
        ]);

        $this->assertSame(['Madrid, España', 'Lisboa'], array_column($choices, 'label'));
    }

    public function testNoSuggestionsProduceNoChoices(): void
    {
        $this->assertSame([], PlaceSuggestionsPresenter::toChoices([]));
    }
}
