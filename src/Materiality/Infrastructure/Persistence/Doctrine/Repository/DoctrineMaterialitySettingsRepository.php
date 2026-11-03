<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.materiality_settings', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMaterialitySettingsRepository implements MaterialitySettingsRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MaterialitySettings $materialitySettings): void
    {
        $this->manager->persist($materialitySettings);
        $this->manager->flush();
    }

    public function findCurrent(): ?MaterialitySettings
    {
        return $this->manager->getRepository(MaterialitySettings::class)->findOneBy([], ['registeredAt' => 'ASC']);
    }
}
