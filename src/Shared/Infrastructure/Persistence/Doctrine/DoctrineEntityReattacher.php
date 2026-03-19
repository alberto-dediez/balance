<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineEntityReattacher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Recorre un objeto y reconecta cualquier entidad de Doctrine que encuentre.
     */
    public function reattach(object $object): object
    {
        $reflectionClass = new \ReflectionClass($object);

        foreach ($reflectionClass->getProperties() as $property) {
            $value = $property->getValue($object);

            if (is_array($value)) {
                $refreshedArray = [];
                foreach ($value as $i => $subValue) {
                    if (!is_object($subValue) || !$this->isDoctrineEntity($subValue)) {
                        $refreshedArray[$i] = $subValue;

                        continue;
                    }

                    $refreshedEntity = $this->refreshEntity($subValue);
                    $refreshedArray[$i] = $refreshedEntity;
                }

                $property->setValue($object, $refreshedArray);

                continue;
            }
            if (!is_object($value)) {
                continue;
            }
            if (!$this->isDoctrineEntity($value)) {
                continue;
            }

            $managedEntity = $this->refreshEntity($value);
            $property->setValue($object, $managedEntity);
        }

        return $object;
    }

    private function isDoctrineEntity(object $object): bool
    {
        $className = $object::class;

        return !$this->entityManager->getMetadataFactory()->isTransient($className);
    }

    private function refreshEntity(object $detachedEntity): ?object
    {
        $className = $detachedEntity::class;
        $metadata = $this->entityManager->getClassMetadata($className);

        $identifier = $metadata->getIdentifierValues($detachedEntity);

        if (empty($identifier)) {
            return null;
        }

        try {
            return $this->entityManager->find($className, $identifier);
        } catch (\Throwable) {
            return null;
        }
    }
}
