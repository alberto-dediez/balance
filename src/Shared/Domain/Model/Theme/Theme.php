<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Theme;

interface Theme
{
    public function slug(): string;

    public function name(): string;

    public function defaults(): ThemeDefaults;

    public function stylesheet(): ?string;

    public function logo(): ?string;

    public function inverseLogo(): ?string;
}
