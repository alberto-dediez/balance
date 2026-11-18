<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Shared\Application\Assistant\ToolResult;

final class ToolResultContent
{
    public static function of(ToolResult $result): string
    {
        $data = $result->data;
        $href = $result->link?->hrefFrom($result->references);

        if (null !== $result->link && null !== $href) {
            $data['_link'] = ['label' => $result->link->label, 'href' => $href];
        }

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
