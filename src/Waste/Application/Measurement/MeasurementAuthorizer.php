<?php

namespace App\Waste\Application\Measurement;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Application\AbstractAuthorizer;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Property\PropertyRepository;

final class MeasurementAuthorizer extends AbstractAuthorizer
{
    public function __construct(private PropertyRepository $propertyRepository)
    {
        
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }
        
        return true;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        $property = $this->propertyRepository->find($subject->propertyId());

        if (null == $property || null == $property->accountId()) {
            return false;
        }

        return 
            $user->accountId()->equals($property->accountId())
            && (
                $user->hasRole(Role::Owner)
                || $user->id()->equals($subject->registeredBy())
            )
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }
        
        return true;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        $property = $this->propertyRepository->find($subject->propertyId());

        if (null == $property || null == $property->accountId()) {
            return false;
        }

        return 
            $user->accountId()->equals($property->accountId())
            && (
                $user->hasRole(Role::Owner)
                || $user->id()->equals($subject->registeredBy())
            )
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        $property = $this->propertyRepository->find($subject->propertyId());

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
    
    public function supports(mixed $subject): bool
    {
        return $subject instanceof Measurement;
    }
}