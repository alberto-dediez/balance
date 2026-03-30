<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\Account\Account;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteEntityType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Account>
 */
class AccountType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Account::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                $qb = $er->createQueryBuilder('u');

                $qb
                    ->where(
                        $qb->expr()->orX(
                            $qb->expr()->isNull('u.removed'),
                            $qb->expr()->eq('u.removed', ':removed')
                        ),
                    )
                    ->setParameter('removed', false, Types::BOOLEAN)
                ;

                return $qb;
            },
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteEntityType::class;
    }
}
