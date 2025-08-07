<?php

namespace App\Shared\Infrastructure\Repository\Locale;

use Doctrine\ORM\EntityRepository;
use App\Shared\Domain\Model\Locale\Translatable;

trait DoctrineTranslatableRepositoryTrait
{
    private function loadTranslations(?Translatable $translatable, EntityRepository $translationRepository)
    {
        if (null === $translatable) {
            return ;
        }

        $translatable->translations()->setLocale($this->localeGetter->locale());

        $storedTranslations = $translationRepository
            ->findBy([
                'translatableId' => $translatable->id()
            ])
        ;

        if (count($storedTranslations) == 0) {
            return ;
        }
        
        foreach ($storedTranslations as $translation) {
            $translatable->translations()->add($translation);
        }
    }

    private function addTranslations(Translatable $translatable, EntityRepository $translationRepository)
    {
        $translations = $translatable->translations();

        if (count($translations) == 0) {
            return ;
        }

        $storedTranslations = $translationRepository->findBy([
            'translatableId' => $translatable->id()
        ]);

        if (count($storedTranslations) > 0) {
            foreach ($storedTranslations as $storedTranslation) {
                if (!$translations->contains($storedTranslation)) {
                    $this->getEntityManager()->remove($storedTranslation);
                }
            }
        }

        foreach ($translations->toArray() as $translation) {
            $this->getEntityManager()->persist($translation);
        }
    }
}