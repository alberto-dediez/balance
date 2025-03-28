<?php

namespace App\Waste\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MeasurementVoter extends Voter
{
    const EDIT = 'edit';
    const LIST = 'list';
    const REGISTER = 'register';
    const REMOVE = 'remove';

    public function __construct(private LoggedUser $loggedUser, private PropertyRepository $propertyRepository)
    {
        
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::LIST, self::EDIT, self::REGISTER, self::REMOVE])) {
            return false;
        }

        if (!$subject instanceof Measurement) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->loggedUser->__invoke();

        if (!$user instanceof User) {
            return false;
        }

        if (null == $user->accountId()) {
            return false;
        }

        /** @var Measurement $measurement */
        $measurement = $subject;

        return match($attribute) {
            self::REGISTER => $this->canRegister($measurement, $user),
            self::LIST => $this->canList($measurement, $user),
            self::EDIT => $this->canEdit($measurement, $user),
            self::REMOVE => $this->canRemove($measurement, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canRegister(Measurement $measurement, User $user): bool
    {
        return true;
    }

    private function canList(Measurement $measurement, User $user): bool
    {
        return true;
    }

    private function canEdit(Measurement $measurement, User $user): bool
    {
        $property = $this->propertyRepository->find($measurement->propertyId());

        if (null == $property || null == $property->accountId()) {
            return false;
        }

        return 
            $user->accountId()->equals($property->accountId())
            && (
                $user->hasRole(Role::Owner)
                || $user->id()->equals($measurement->registeredBy())
            )
        ;
    }

    private function canRemove(Measurement $measurement, User $user): bool
    {
        $property = $this->propertyRepository->find($measurement->propertyId());

        if (null == $property || null == $property->accountId()) {
            return false;
        }

        return 
            $user->accountId()->equals($property->accountId())
            && (
                $user->hasRole(Role::Owner)
            )
        ;
    }
}