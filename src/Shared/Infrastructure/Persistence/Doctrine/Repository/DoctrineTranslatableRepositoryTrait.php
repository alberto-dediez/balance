<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\Translatable;
use Doctrine\ORM\EntityRepository;

trait DoctrineTranslatableRepositoryTrait
{
    private function loadTranslations(?Translatable $translatable, EntityRepository $translationRepository): void
    {
        if (!$translatable instanceof Translatable) {
            return;
        }

        $translatable->translations()->setLocale($this->localeGetter->locale());

        $storedTranslations = $translationRepository
            ->findBy([
                'translatableId' => $translatable->id(),
            ])
        ;

        if (0 === count($storedTranslations)) {
            return;
        }

        foreach ($storedTranslations as $translation) {
            $translatable->translations()->add($translation);
        }
    }

    private function addTranslations(Translatable $translatable, EntityRepository $translationRepository): void
    {
        $translations = $translatable->translations();

        if (0 === count($translations)) {
            return;
        }

        $storedTranslations = $translationRepository->findBy([
            'translatableId' => $translatable->id(),
        ]);

        foreach ($storedTranslations as $storedTranslation) {
            if (!$translations->contains($storedTranslation)) {
                $this->getEntityManager()->remove($storedTranslation);
            }
        }

        foreach ($translations->toArray() as $translation) {
            $this->getEntityManager()->persist($translation);
        }
    }
}
