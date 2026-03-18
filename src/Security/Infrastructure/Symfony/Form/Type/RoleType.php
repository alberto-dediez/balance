<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Role>
 */
class RoleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => Role::casesForOwners(),
            'translation_domain' => 'roles',
        ]);
    }

    public function getParent()
    {
        return AutocompleteType::class;
    }
}
