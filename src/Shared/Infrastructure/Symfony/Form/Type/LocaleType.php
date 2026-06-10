<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Locale> */
class LocaleType extends AbstractType
{
    /** @var list<Locale> */
    private readonly array $allowedLocales;

    public function __construct(string $localeList)
    {
        $codes = explode('|', $localeList);

        $this->allowedLocales = array_values(
            array_filter(
                array_map(fn(string $code) => Locale::tryFrom(trim($code)), $codes),
                fn(?Locale $locale) => $locale instanceof Locale,
            ),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $displayLocale = LocaleGetter::locale()->value;

        $resolver->setDefaults([
            'choices' => $this->allowedLocales,
            'choice_label' => fn(Locale $locale) => ucfirst((string) \Locale::getDisplayLanguage($locale->value, $displayLocale)),
            'choice_value' => fn(?Locale $locale) => $locale?->value,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
