<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Styles;

use PHPUnit\Framework\TestCase;

class BrandingStylesheetTest extends TestCase
{
    private const string SCOPE = 'body.branded-page';

    /**
     * @return list<array{0: string}>
     */
    public static function brandingStylesheets(): array
    {
        $files = array_merge(
            [self::projectDir() . '/assets/styles/_branding.scss'],
            glob(self::projectDir() . '/assets/styles/themes/*.scss') ?: [],
        );

        return array_map(static fn(string $file): array => [$file], $files);
    }

    /**
     * @dataProvider brandingStylesheets
     */
    public function testEveryRuleIsScopedToTheBrandedPage(string $file): void
    {
        $source = $this->withoutComments((string) file_get_contents($file));

        $depth = 0;
        $buffer = '';

        foreach (str_split($source) as $character) {
            if ('{' === $character) {
                if (0 === $depth) {
                    $this->assertSame(
                        self::SCOPE,
                        trim($buffer),
                        sprintf('Top-level selector outside %s in %s', self::SCOPE, basename($file)),
                    );
                }

                ++$depth;
                $buffer = '';

                continue;
            }

            if ('}' === $character) {
                --$depth;
                $buffer = '';

                $this->assertGreaterThanOrEqual(0, $depth, sprintf('Unbalanced braces in %s', basename($file)));

                continue;
            }

            if (0 === $depth) {
                $buffer .= $character;
            }
        }

        $this->assertSame(0, $depth, sprintf('Unbalanced braces in %s', basename($file)));
        $this->assertSame('', trim($buffer), sprintf('Statement outside %s in %s', self::SCOPE, basename($file)));
    }

    /**
     * @dataProvider brandingStylesheets
     */
    public function testItNeverTargetsTheBareRoot(string $file): void
    {
        $this->assertStringNotContainsString(
            ':root',
            $this->withoutComments((string) file_get_contents($file)),
            sprintf('%s must never reach :root', basename($file)),
        );
    }

    public function testTheBrandingLayerIsImportedLast(): void
    {
        $appScss = (string) file_get_contents(self::projectDir() . '/assets/styles/app.scss');

        $imports = [];
        preg_match_all('/^@import\s+"([^"]+)";$/m', $appScss, $imports);

        $this->assertSame('./branding', end($imports[1]));
    }

    private function withoutComments(string $source): string
    {
        return (string) preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $source);
    }

    private static function projectDir(): string
    {
        return dirname(__DIR__, 5);
    }
}
