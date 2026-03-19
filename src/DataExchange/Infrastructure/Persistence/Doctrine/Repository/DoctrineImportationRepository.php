<?php

namespace App\DataExchange\Infrastructure\Persistence\Doctrine\Repository;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationDataRepository;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\Importation\NotImportedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.importation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineImportationRepository implements ImportationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager, private readonly ImportationDataRepository $dataRepository)
    {

    }

    public function add(Importation $importation): void
    {
        try {
            $recoverImportation = $this->find($importation->id());

            if (!$recoverImportation instanceof Importation) {
                $this->dataRepository->create($importation);
            }

            $this->manager->persist($importation);
            $this->manager->flush();

            if (count($importation->configurations()) === 0) {
                return ;
            }

            foreach ($importation->configurations() as $configuration) {
                $this->dataRepository->saveConfiguration($importation->id(), $configuration);
            }
        } catch (\Throwable $exception) {
            throw new NotImportedException($exception->getMessage());
        }
    }

    public function find(ImportationId $importationId): ?Importation
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('i')
            ->from(Importation::class, 'i')
        ;

        $importation = $qb
            ->andWhere(
                $qb->expr()->eq('i.id', ':id')
            )
            ->setParameter('id', $importationId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $importation) {
            return null;
        }

        if (count($importation->configurations()) === 0) {
            $importation->loadConfigurations(
                $this->dataRepository->getAllConfigurations($importation->id())
            );
        }

        return $importation;
    }

    public function getData(ImportationId $importationId, int $rowNumber): ?array
    {
        return $this->dataRepository->getData($importationId, $rowNumber);
    }

    public function saveResult(ImportationId $importationId, int $rowNumber, ?string $error): void
    {
        $this->dataRepository->saveResult($importationId, $rowNumber, $error);
    }

    public function getResult(ImportationId $importationId, int $rowNumber): ?array
    {
        return $this->dataRepository->getResult($importationId, $rowNumber);
    }

    public function remove(Importation $importation): void
    {
        $this->dataRepository->remove($importation->id());

        $this->manager->remove($importation);
        $this->manager->flush();
    }

    public function pendingRows(ImportationId $importationId): int
    {
        return $this->dataRepository->pendingRows($importationId);
    }
}
