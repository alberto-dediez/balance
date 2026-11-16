<?php

declare(strict_types=1);

namespace App\Security\Application\User\Assistant;

use App\Security\Application\Action;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\Username;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolPreview;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\Assistant\WriteAssistantTool;

class RegisterUserTool implements WriteAssistantTool
{
    public function __construct(
        private readonly RegisterUserService $registerUser,
        private readonly UserRepository $users,
        private readonly AccountRepository $accounts,
        private readonly UserAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'register_user';
    }

    public function label(): string
    {
        return 'Registrar usuario';
    }

    public function description(): string
    {
        return 'Registra un usuario nuevo, opcionalmente en una cuenta. No recibe contraseña: el usuario recibe el email de bienvenida de la plataforma y establece la suya con «recuperar contraseña» en la pantalla de acceso, así que el nombre de usuario debe ser un email válido. Avísalo al proponer el plan.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Write;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('username', 'Usuario', 'Nombre de usuario; normalmente el email de la persona.')->required(),
            ToolParameter::id('accountId', 'Cuenta', 'Id de la cuenta a la que pertenece el usuario.'),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new User(UserId::generate()), Action::Register, $user);
    }

    public function preview(ToolArguments $arguments, User $user, ToolContext $context): ToolPreview
    {
        if (!$this->isAvailableTo($user)) {
            throw InvalidToolArgumentsException::because('No tienes permiso para registrar usuarios.');
        }

        $username = (string) $arguments->string('username');

        if (1 !== preg_match(Username::PATTERN, $username)) {
            throw InvalidToolArgumentsException::because('El nombre de usuario no puede contener < ni >.');
        }

        if ($this->users->findByUsername($username) instanceof User) {
            throw InvalidToolArgumentsException::because(sprintf('El usuario «%s» ya existe.', $username));
        }

        $displays = [];

        if ($arguments->has('accountId')) {
            $account = $this->accounts->find(new AccountId((string) $arguments->string('accountId')));

            if (!$account instanceof Account) {
                throw InvalidToolArgumentsException::because('La cuenta no existe.');
            }

            $displays['accountId'] = $account->name();
        }

        return new ToolPreview($displays);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $accountId = $arguments->string('accountId');
        $username = (string) $arguments->string('username');

        try {
            $response = ($this->registerUser)(
                new RegisterUserRequest(
                    username: $username,
                    password: $this->randomPassword(),
                    accountId: null === $accountId ? null : new AccountId($accountId),
                ),
                $user,
            );
        } catch (DuplicatedUsernameException) {
            throw InvalidToolArgumentsException::because(sprintf('El usuario «%s» ya existe.', $username));
        }

        return new ToolResult(
            summary: sprintf('Usuario «%s» registrado; debe establecer su contraseña con «recuperar contraseña»', $response->username),
            data: ['userId' => (string) $response->userId, 'username' => $response->username],
            references: ['userId' => (string) $response->userId],
            link: new ToolLink('Ver usuario', 'admin_users_edit', ['userId']),
        );
    }

    private function randomPassword(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
