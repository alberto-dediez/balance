<?php

namespace App\Security\Infrastructure\Symfony\Type;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RoleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => Role::casesForOwners(),
            'translation_domain' => 'roles',
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }
}