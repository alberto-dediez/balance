<?php

namespace App\DataExchange\Infrastructure\UI\Web\DTO;

use Symfony\Component\Translation\TranslatableMessage;

class TranslationDefinition
{
    public function __construct(
        public string $fieldType,
        public array $options = []
    ) {
        $this->options['required'] = $this->options['required'] ?? false;
        $this->options['placeholder'] = $this->options['placeholder'] ?? new TranslatableMessage('Ignorar');
    }
}
