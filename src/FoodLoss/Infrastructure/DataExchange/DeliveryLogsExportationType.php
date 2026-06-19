<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DeliveryLog\Export\DeliveryLogsExportFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Infrastructure\Symfony\Form\ListDeliveryLogsExportType;
use App\FoodLoss\Infrastructure\Symfony\Twig\RecipientOrganizationExtension;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeliveryLogsExportationType implements ExportationType
{
    public function __construct(
        private readonly DeliveryLogRepository $repository,
        private readonly DeliveryLogAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly PropertyExtension $propertyExtension,
        private readonly RecipientOrganizationExtension $recipientOrganizationExtension,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    public function code(): string
    {
        return 'delivery-logs';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Registro de entregas');
    }

    public function returnRoute(): string
    {
        return 'delivery_logs';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('date', new TranslatableMessage('Fecha estimada de entrega')),
            new ExportColumnDefinition('quantity', new TranslatableMessage('Cantidad')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('productType', new TranslatableMessage('Tipo de producto')),
            new ExportColumnDefinition('recipientOrganizationId', new TranslatableMessage('Entidad receptora')),
            new ExportColumnDefinition('propertyId', new TranslatableMessage('Propiedad')),
            new ExportColumnDefinition('delivered', new TranslatableMessage('Estado')),
            new ExportColumnDefinition('deliveredAt', new TranslatableMessage('Fecha entrega')),
            new ExportColumnDefinition('pickupType', new TranslatableMessage('Tipo de entrega')),
        ];
    }

    public function filterFormClass(): string
    {
        return ListDeliveryLogsExportType::class;
    }

    public function createFilter(): object
    {
        return new DeliveryLogsExportFilter(
            accountId: $this->loggedAccount->__invoke()?->id(),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof DeliveryLogsExportFilter) {
            return [];
        }

        return [
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'productType' => $filter->productType instanceof FoodType ? $filter->productType->value : null,
            'recipientOrganizationId' => $filter->recipientOrganizationId instanceof RecipientOrganizationId ? (string) $filter->recipientOrganizationId : null,
            'propertyId' => $filter->propertyId instanceof PropertyId ? (string) $filter->propertyId : null,
            'dateFrom' => $filter->dateFrom?->format('Y-m-d'),
            'dateTo' => $filter->dateTo?->format('Y-m-d'),
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new DeliveryLogsExportFilter();

        $filter->accountId = isset($criteria['accountId']) ? new AccountId((string) $criteria['accountId']) : null;
        $filter->productType = isset($criteria['productType']) ? FoodType::from((string) $criteria['productType']) : null;
        $filter->recipientOrganizationId = isset($criteria['recipientOrganizationId']) ? new RecipientOrganizationId((string) $criteria['recipientOrganizationId']) : null;
        $filter->propertyId = isset($criteria['propertyId']) ? new PropertyId((string) $criteria['propertyId']) : null;
        $filter->dateFrom = isset($criteria['dateFrom']) ? new \DateTimeImmutable((string) $criteria['dateFrom']) : null;
        $filter->dateTo = isset($criteria['dateTo']) ? new \DateTimeImmutable((string) $criteria['dateTo']) : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof DeliveryLogsExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $deliveryLog) {
            $status = match (true) {
                $deliveryLog->isDelivered() => $this->translator->trans('Entregado'),
                $deliveryLog->isInSignatureProcess() => $this->translator->trans('En proceso de firma'),
                default => $this->translator->trans('Pendiente'),
            };

            yield [
                'date' => $deliveryLog->date()->format('d/m/Y'),
                'quantity' => $deliveryLog->quantity(),
                'unit' => $this->translator->trans($deliveryLog->unit()->value, [], 'deliveryLogUnit'),
                'productType' => $this->translator->trans($deliveryLog->productType()->value, [], 'foodType'),
                'recipientOrganizationId' => null !== $deliveryLog->recipientOrganizationId()
                    ? (string) $this->recipientOrganizationExtension->recipientOrganizationName($deliveryLog->recipientOrganizationId())
                    : '',
                'propertyId' => null !== $deliveryLog->propertyId()
                    ? (string) $this->propertyExtension->propertyName($deliveryLog->propertyId())
                    : '',
                'delivered' => $status,
                'deliveredAt' => null !== $deliveryLog->deliveredAt() ? $deliveryLog->deliveredAt()->format('d/m/Y') : '',
                'pickupType' => null !== $deliveryLog->pickupType()
                    ? $this->translator->trans($deliveryLog->pickupType()->value, [], 'pickupType')
                    : '',
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new DeliveryLog(
            id: DeliveryLogId::generate(),
            accountId: $user->accountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }
}
