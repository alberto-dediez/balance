<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\Translatable;
use App\Waste\Domain\Model\Waste\WasteTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

trait DoctrineTranslatableRepositoryTrait
{
    private function addTranslations(Translatable $translatable, string $translationClass, EntityManagerInterface $manager): void
    {
        $translationRepository = $manager->getRepository($translationClass);

        if (count($translatable->translations()) > 0) {
            $qb = $translationRepository->createQueryBuilder('t');

            $reflection = new \ReflectionClass($translationClass);

            $qb->update()
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->eq('t.locale', ':locale'),
                        $qb->expr()->eq('t.translatable', ':translatable')
                    )
                )
                ->setParameter('translatable', $translatable)
            ;

            foreach ($reflection->getProperties() as $property) {
                if (in_array($property->getName(), ['locale', 'translatable'], true)) {
                    continue;
                }

                $qb->set('t.' . $property->getName(), ':' . $property->getName());
            }

            foreach ($translatable->translations() as $translation) {
                if (!$manager->contains($translation)) {
                    $manager->persist($translation);
                } else {
                    foreach ($reflection->getProperties() as $property) {
                        if (in_array($property->getName(), ['locale', 'translatable'], true)) {
                            continue;
                        }

                        $propertyName = $property->getName();

                        $qb->setParameter($propertyName, $translation->$propertyName());
                    }

                    $qb
                        ->setParameter('locale', $translation->locale())
                        ->getQuery()
                        ->execute()
                    ;
                }
            }
        }
    }
}
