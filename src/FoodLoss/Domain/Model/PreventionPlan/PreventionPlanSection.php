<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class PreventionPlanSection implements Translatable
{
    /** @var iterable<PreventionPlanSectionTranslation>|Translations */
    private iterable $translations;

    public function __construct(
        private readonly PreventionPlanSectionId $id,
        private readonly PreventionPlanVersion $preventionPlanVersion,
        private string $title,
        private string $content,
    ) {
        $this->translations = new Translations();
    }

    public function id(): PreventionPlanSectionId
    {
        return $this->id;
    }

    public function preventionPlanVersion(): PreventionPlanVersion
    {
        return $this->preventionPlanVersion;
    }

    public function title(): string
    {
        /** @var PreventionPlanSectionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->title() : $this->title;
    }

    public function content(): string
    {
        /** @var PreventionPlanSectionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->content() : $this->content;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function translate(string $title, string $content, Locale $locale): void
    {
        $this->translations()->add(
            new PreventionPlanSectionTranslation(
                title: $title,
                content: $content,
                locale: $locale,
                translatable: $this,
            ),
        );
    }
}
