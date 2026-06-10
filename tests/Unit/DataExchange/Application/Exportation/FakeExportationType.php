<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatableMessage;

class FakeExportationType implements ExportationType
{
    /** @param array<int, array<string, scalar|null>> $rows */
    public function __construct(
        private readonly bool $authorized = true,
        private readonly array $rows = [
            ['username' => 'alice', 'roles' => 'Admin'],
            ['username' => 'bob', 'roles' => 'User'],
        ],
        private readonly string $code = 'users',
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string|TranslatableMessage
    {
        return 'Users';
    }

    public function returnRoute(): string
    {
        return 'admin_users';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('username', new TranslatableMessage('Username')),
            new ExportColumnDefinition('roles', new TranslatableMessage('Roles')),
        ];
    }

    public function filterFormClass(): string
    {
        return TextType::class;
    }

    public function createFilter(): object
    {
        return new \stdClass();
    }

    public function dehydrateFilter(object $filter): array
    {
        return [];
    }

    public function hydrateFilter(array $criteria): object
    {
        return new \stdClass();
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        yield from $this->rows;
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorized;
    }
}
