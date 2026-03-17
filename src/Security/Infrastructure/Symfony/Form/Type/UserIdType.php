<?php

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\User\UserId;
use Symfony\Component\Routing\RouterInterface;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdRemoteType;

class UserIdType extends AggregateRootIdRemoteType
{
    public function __construct(private readonly RouterInterface $router, private readonly UserRepository $repository)
    {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete-path' => $this->router->generate('admin_users_autocomplete'),
            'repository' => $this->repository,
            'class' => UserId::class      
        ]);

        parent::configureOptions($resolver);
    }
}