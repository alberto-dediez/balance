<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaType;
use PHPUnit\Framework\TestCase;

class MediaFilterTest extends TestCase
{
    public function testItKeepsTheGivenCriteria(): void
    {
        $filter = new MediaFilter(
            name: 'logo',
            type: MediaType::Image,
            withoutAccount: true,
            limit: 5,
        );

        $this->assertEquals('logo', $filter->name);
        $this->assertEquals(MediaType::Image, $filter->type);
        $this->assertTrue($filter->withoutAccount);
        $this->assertEquals(5, $filter->limit);
    }

    /**
     * Filters are kept in the session and restored without calling the
     * constructor, so every field must stay readable even for filters stored
     * before that field existed.
     *
     * @dataProvider filterClasses
     *
     * @param class-string<MediaFilter> $class
     */
    public function testItIsUsableWhenRestoredWithoutTheConstructor(string $class): void
    {
        $filter = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        $this->assertNull($filter->withoutAccount);
    }

    /**
     * @return array<string, array{class-string<MediaFilter>}>
     */
    public static function filterClasses(): array
    {
        return [
            'filter' => [MediaFilter::class],
            'list request' => [ListMediaRequest::class],
        ];
    }
}
