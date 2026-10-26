<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;

class User implements \Stringable
{
    use AggregateRoot;

    /**
     * @param Role[]        $roles
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected UserId $id,
        protected string $username = '',
        protected string $password = '',
        protected ?AccountId $accountId = null,
        protected ?Account $account = null,
        protected array $roles = [],
        protected ?RecoverToken $recoverToken = null,
        array $events = [],
        protected bool $removed = false,
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null,
        protected ?Locale $preferredLocale = null,
        protected ?MediaId $pictureId = null,
        protected ?TwoFactorMethod $twoFactorMethod = null,
        protected ?string $totpSecret = null,
        protected ?string $pendingTotpSecret = null,
        protected ?TwoFactorChallenge $twoFactorChallenge = null,
    ) {
        $this->events = $events;
    }

    public function __toString(): string
    {
        return $this->username();
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function account(): ?Account
    {
        return $this->account;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function recoverToken(): ?RecoverToken
    {
        return $this->recoverToken;
    }

    public function isRecoverTokenValid(RecoverToken $token): bool
    {
        return $this->recoverToken() instanceof RecoverToken && $this->recoverToken()->equals($token) && !$this->recoverToken()->isExpired();
    }

    /**
     * @return Role[]
     */
    public function roles(): array
    {
        if (0 === count($this->roles)) {
            return [Role::User];
        }

        $roles = $this->roles;

        if (false === $this->hasRole(Role::User)) {
            $roles[] = Role::User;
        }

        return array_values($roles);
    }

    public function hasRole(Role $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function isActive(): bool
    {
        return null == $this->account() ? true : $this->account()->isActive();
    }

    public function registeredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function preferredLocale(): ?Locale
    {
        return $this->preferredLocale;
    }

    public function pictureId(): ?MediaId
    {
        return $this->pictureId;
    }

    public function hasPicture(): bool
    {
        return $this->pictureId instanceof MediaId;
    }

    public function twoFactorMethod(): ?TwoFactorMethod
    {
        return $this->twoFactorMethod;
    }

    public function hasTwoFactor(): bool
    {
        return $this->twoFactorMethod instanceof TwoFactorMethod;
    }

    public function totpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function pendingTotpSecret(): ?string
    {
        return $this->pendingTotpSecret;
    }

    public function hasPendingTotpEnrolment(): bool
    {
        return null !== $this->pendingTotpSecret;
    }

    public function twoFactorChallenge(): TwoFactorChallenge
    {
        return $this->twoFactorChallenge ?? TwoFactorChallenge::none();
    }

    public function register(string $username, string $password, ?AccountId $accountId = null, bool $welcome = true): void
    {
        $this->recordApplyAndPublish(
            new UserRegistered(
                userId: $this->id(),
                username: (string) new Username($username),
                password: $password,
                accountId: $accountId,
                welcome: $welcome,
            ),
        );
    }

    public function grantRole(Role $role): void
    {
        $this->recordApplyAndPublish(
            new RoleGranted(
                $this->id(),
                $role,
            ),
        );
    }

    public function ungrantRole(Role $role): void
    {
        $this->recordApplyAndPublish(
            new RoleUngranted(
                $this->id(),
                $role,
            ),
        );
    }

    public function changePassword(string $password): void
    {
        $this->recordApplyAndPublish(
            new PasswordChanged(
                userId: $this->id(),
                password: $password,
            ),
        );
    }

    public function changePreferredLocale(Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new PreferredLocaleChanged(
                userId: $this->id(),
                locale: $locale,
            ),
        );
    }

    public function changePicture(?MediaId $pictureId): void
    {
        $this->recordApplyAndPublish(
            new UserPictureChanged(
                userId: $this->id(),
                pictureId: $pictureId,
            ),
        );
    }

    public function initPasswordRecovery(): void
    {
        $this->recordApplyAndPublish(
            new PasswordRecoveryInitialized(
                userId: $this->id(),
                username: $this->username(),
                token: RecoverToken::generate(),
            ),
        );
    }

    public function recoverPassword(RecoverToken $token, string $password): void
    {
        if (!$this->isRecoverTokenValid($token)) {
            throw new RecoverTokenInvalidException($token, $this->recoverToken?->expirateAt());
        }

        $this->recordApplyAndPublish(
            new PasswordRecovered(
                userId: $this->id(),
                password: $password,
                token: $token,
            ),
        );
    }

    public function enableTwoFactorByEmail(): void
    {
        $this->recordApplyAndPublish(
            new TwoFactorEnrolmentConfirmed(
                userId: $this->id(),
                method: TwoFactorMethod::Email,
            ),
        );
    }

    public function requestTotpEnrolment(#[\SensitiveParameter] string $encryptedSecret): void
    {
        $this->recordApplyAndPublish(
            new TwoFactorEnrolmentRequested(
                userId: $this->id(),
                method: TwoFactorMethod::Totp,
                encryptedSecret: $encryptedSecret,
                challenge: TwoFactorChallenge::withoutCode(),
            ),
        );
    }

    public function confirmTotpEnrolment(string $code, TotpAuthenticator $totp): void
    {
        if (null === $this->pendingTotpSecret || !$this->twoFactorChallenge()->isPending()) {
            throw new TwoFactorNotPendingException();
        }

        if (!$totp->verify($this->pendingTotpSecret, $code)) {
            $this->failTwoFactorVerification(TwoFactorMethod::Totp);
        }

        $this->recordApplyAndPublish(
            new TwoFactorEnrolmentConfirmed(
                userId: $this->id(),
                method: TwoFactorMethod::Totp,
            ),
        );
    }

    public function cancelTotpEnrolment(): void
    {
        if (!$this->hasPendingTotpEnrolment()) {
            return;
        }

        $this->recordApplyAndPublish(
            new TwoFactorEnrolmentCancelled(
                userId: $this->id(),
            ),
        );
    }

    public function disableTwoFactor(): void
    {
        if (!$this->hasTwoFactor() && !$this->hasPendingTotpEnrolment() && !$this->twoFactorChallenge()->expiresAt() instanceof \DateTimeImmutable) {
            return;
        }

        $this->recordApplyAndPublish(
            new TwoFactorDisabled(
                userId: $this->id(),
            ),
        );
    }

    public function requestTwoFactorChallenge(TwoFactorMethod $method, TwoFactorChallenge $challenge, string $maskedDestination): void
    {
        $this->recordApplyAndPublish(
            new TwoFactorChallengeRequested(
                userId: $this->id(),
                method: $method,
                challenge: $challenge,
                maskedDestination: $maskedDestination,
                expiresAt: $challenge->expiresAt(),
            ),
        );
    }

    public function verifyTwoFactor(string $code, TwoFactorMethod $method, #[\SensitiveParameter] string $appSecret, TotpAuthenticator $totp): void
    {
        if (!$this->twoFactorChallenge()->isPending()) {
            throw new TwoFactorNotPendingException();
        }

        $isValid = match ($method) {
            TwoFactorMethod::Totp => $totp->verify($this->totpSecret ?? '', $code),
            TwoFactorMethod::Email => $this->twoFactorChallenge()->verify($code, $appSecret),
        };

        if (!$isValid) {
            $this->failTwoFactorVerification($method);
        }

        $this->recordApplyAndPublish(
            new TwoFactorVerified(
                userId: $this->id(),
                method: $method,
            ),
        );
    }

    private function failTwoFactorVerification(TwoFactorMethod $method): never
    {
        $this->recordApplyAndPublish(
            new TwoFactorVerificationFailed(
                userId: $this->id(),
                method: $method,
            ),
        );

        throw new TwoFactorCodeInvalidException($this->twoFactorChallenge()->attemptsLeft(), $this->twoFactorChallenge()->isLocked());
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new UserRemoved(
                $this->id(),
            ),
        );
    }

    public function applyUserRegistered(UserRegistered $event): void
    {
        $this->username = $event->username();
        $this->password = $event->password();
        $this->accountId = $event->accountId();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyRoleGranted(RoleGranted $event): void
    {
        if (in_array($event->role(), $this->roles)) {
            return;
        }

        $this->roles[] = $event->role();
    }

    public function applyRoleUngranted(RoleUngranted $event): void
    {
        if (!in_array($event->role(), $this->roles)) {
            return;
        }

        $key = array_search($event->role(), $this->roles);

        unset($this->roles[$key]);
    }

    public function applyPasswordChanged(PasswordChanged $event): void
    {
        $this->password = $event->password();
    }

    public function applyPreferredLocaleChanged(PreferredLocaleChanged $event): void
    {
        $this->preferredLocale = $event->locale();
    }

    public function applyUserPictureChanged(UserPictureChanged $event): void
    {
        $this->pictureId = $event->pictureId();
    }

    public function applyPasswordRecoveryInitialized(PasswordRecoveryInitialized $event): void
    {
        $this->recoverToken = $event->token();
    }

    public function applyPasswordRecovered(PasswordRecovered $event): void
    {
        $this->password = $event->password();
        $this->recoverToken = null;
    }

    public function applyTwoFactorEnrolmentRequested(TwoFactorEnrolmentRequested $event): void
    {
        $this->pendingTotpSecret = $event->encryptedSecret();
        $this->twoFactorChallenge = $event->challenge();
    }

    public function applyTwoFactorEnrolmentConfirmed(TwoFactorEnrolmentConfirmed $event): void
    {
        $this->twoFactorMethod = $event->method();
        $this->totpSecret = TwoFactorMethod::Totp === $event->method() ? $this->pendingTotpSecret : null;
        $this->pendingTotpSecret = null;
        $this->twoFactorChallenge = TwoFactorChallenge::none();
    }

    public function applyTwoFactorEnrolmentCancelled(TwoFactorEnrolmentCancelled $event): void
    {
        $this->pendingTotpSecret = null;
        $this->twoFactorChallenge = TwoFactorChallenge::none();
    }

    public function applyTwoFactorDisabled(TwoFactorDisabled $event): void
    {
        $this->twoFactorMethod = null;
        $this->totpSecret = null;
        $this->pendingTotpSecret = null;
        $this->twoFactorChallenge = TwoFactorChallenge::none();
    }

    public function applyTwoFactorChallengeRequested(TwoFactorChallengeRequested $event): void
    {
        $this->twoFactorChallenge = $event->challenge();
    }

    public function applyTwoFactorVerificationFailed(TwoFactorVerificationFailed $event): void
    {
        $this->twoFactorChallenge = $this->twoFactorChallenge()->withFailedAttempt();
    }

    public function applyTwoFactorVerified(TwoFactorVerified $event): void
    {
        $this->twoFactorChallenge = TwoFactorChallenge::none();
    }

    public function applyUserRemoved(UserRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable('NOW');
    }
}
