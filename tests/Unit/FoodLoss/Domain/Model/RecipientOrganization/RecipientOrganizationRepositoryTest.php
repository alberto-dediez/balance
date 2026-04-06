<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationFilter;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RecipientOrganizationRepositoryTest extends KernelTestCase
{
    public function testAddRecipientOrganizationToRepository(): void
    {
        $repository = $this->createSUT();

        $recipientOrganization = RecipientOrganizationMother::registeredRecipientOrganization();

        $this->assertNull($repository->find($recipientOrganization->id()));

        $repository->add($recipientOrganization);

        $recoverRecipientOrganization = $repository->find($recipientOrganization->id());

        $this->assertNotNull($recoverRecipientOrganization);
        $this->assertEquals($recipientOrganization->id(), $recoverRecipientOrganization->id());
    }

    public function testRemoveRecipientOrganizationFromRepository(): void
    {
        $repository = $this->createSUT();

        $recipientOrganization = RecipientOrganizationMother::registeredRecipientOrganization();

        $repository->add($recipientOrganization);

        $recoverRecipientOrganization = $repository->find($recipientOrganization->id());

        $this->assertNotNull($recoverRecipientOrganization);

        $repository->remove($recipientOrganization);

        $this->assertNull($repository->find($recipientOrganization->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::fromName('Nombre específico'));

        $filter = new RecipientOrganizationFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new RecipientOrganizationFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterContact(): void
    {
        $repository = $this->createSUT();

        $repository->add(RecipientOrganizationMother::fromContact('Juan Pérez', 'juan@example.com', '600000001'));
        $repository->add(RecipientOrganizationMother::fromContact('María García', 'maria@example.com', '600000002'));
        $repository->add(RecipientOrganizationMother::fromContact('Luis López', 'luis@test.com', '600000003'));

        // Search by name
        $filter = new RecipientOrganizationFilter(contact: 'Juan');
        $this->assertCount(1, $repository->findByFilter($filter));

        // Search by email
        $filter = new RecipientOrganizationFilter(contact: 'maria@example.com');
        $this->assertCount(1, $repository->findByFilter($filter));

        // Search by phone
        $filter = new RecipientOrganizationFilter(contact: '600000003');
        $this->assertCount(1, $repository->findByFilter($filter));

        // Search partial
        $filter = new RecipientOrganizationFilter(contact: 'example.com');
        $this->assertCount(2, $repository->findByFilter($filter));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::random());
        $repository->add(RecipientOrganizationMother::fromName('Nombre específico'));

        $filter = new RecipientOrganizationFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $recipientOrganization = RecipientOrganizationMother::registeredRecipientOrganization();

        $repository->add($recipientOrganization);

        $recoverRecipientOrganization = $repository->find($recipientOrganization->id());

        $this->assertNotNull($recoverRecipientOrganization);

        $recoverRecipientOrganization->remove();

        $repository->add($recipientOrganization);

        $this->assertNull($repository->find($recipientOrganization->id()));
    }

    abstract protected function createSUT(): RecipientOrganizationRepository;
}
