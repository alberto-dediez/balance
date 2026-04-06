<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RecipientOrganizationExtension extends AbstractExtension
{
    /** @var RecipientOrganization[] */
    private array $recipientOrganizations = [];

    public function __construct(private readonly RecipientOrganizationRepository $repository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('recipient_organization_name', $this->recipientOrganizationName(...)),
        ];
    }

    public function recipientOrganizationName(RecipientOrganizationId $id): ?RecipientOrganization
    {
        if (!isset($this->recipientOrganizations[(string) $id])) {
            $recipientOrganization = $this->repository->find($id);

            if (null !== $recipientOrganization) {
                $this->recipientOrganizations[(string) $id] = $recipientOrganization;
            }
        }

        return $this->recipientOrganizations[(string) $id] ?? null;
    }
}
