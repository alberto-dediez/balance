<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Security\Domain\Model\Branding\HexColor;
use Symfony\Component\Translation\TranslatableMessage;

class ContrastWarnings
{
    private const float MINIMUM_RATIO = 4.5;

    /**
     * @return list<TranslatableMessage>
     */
    public function for(BrandingTokens $tokens): array
    {
        $warnings = [];

        foreach ([
            'Texto sobre el fondo' => [$tokens->bodyColor, $tokens->bodyBg],
            'Color principal sobre el fondo' => [$tokens->primary, $tokens->bodyBg],
            'Texto blanco sobre el color principal' => [new HexColor('#FFFFFF'), $tokens->primary],
        ] as $label => [$foreground, $background]) {
            $ratio = $foreground->contrastRatio($background);

            if ($ratio >= self::MINIMUM_RATIO) {
                continue;
            }

            $warnings[] = new TranslatableMessage(
                '{pair}: contraste {ratio}:1, por debajo del mínimo recomendado de 4.5:1.',
                ['{pair}' => new TranslatableMessage($label), '{ratio}' => number_format($ratio, 2, ',', '.')],
            );
        }

        return $warnings;
    }
}
