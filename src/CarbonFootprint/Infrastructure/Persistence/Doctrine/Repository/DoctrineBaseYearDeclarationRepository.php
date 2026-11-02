<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.base_year_declaration', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineBaseYearDeclarationRepository implements BaseYearDeclarationRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(BaseYearDeclaration $declaration): void
    {
        $this->manager->persist($declaration);
        $this->manager->flush();
    }

    public function remove(BaseYearDeclaration $declaration): void
    {
        $this->manager->remove($declaration);
        $this->manager->flush();
    }

    public function find(BaseYearDeclarationId $id): ?BaseYearDeclaration
    {
        return $this->manager->find(BaseYearDeclaration::class, $id);
    }

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $subjectId): ?BaseYearDeclaration
    {
        return $this->manager->getRepository(BaseYearDeclaration::class)->findOneBy([
            'subjectType' => $subjectType,
            'subjectId' => $subjectId,
        ]);
    }
}
