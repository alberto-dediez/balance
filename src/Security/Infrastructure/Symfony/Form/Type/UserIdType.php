<?php

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class UserIdType extends AggregateRootIdType
{
    public function __construct(private UserRepository $userRepository)
    {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $users = $this->userRepository->findByFilter(new UserFilter());

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($users),      
            'class' => UserId::class      
        ));
    }
}