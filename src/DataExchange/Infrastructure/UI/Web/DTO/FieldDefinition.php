<?php

namespace App\DataExchange\Infrastructure\UI\Web\DTO;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatableMessage;

class FieldDefinition
{
    public function __construct(
        public string $fieldType,
        public array $options = []
    ) {
        $this->options['required'] ??= false;

        if ($this->fieldType === TextType::class) {
            $this->options['attr']['placeholder'] ??= new TranslatableMessage('Ignorar');
        } else {
            $this->options['placeholder'] ??= new TranslatableMessage('Ignorar');
        }

    }
}
