<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use App\Security\Domain\Model\User\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class UserFixtures extends Fixture
{
    public const ADMIN_USER = 'admin-user';

    public const NORMAL_USER = 'normal-user';

    public function load(ObjectManager $manager): void
    {
        $user = UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin);

        $manager->persist($user);

        $this->addReference(self::ADMIN_USER, $user);

        $user = UserMother::fromUsernameAndRole('employee@dediez.software', Role::User);

        $manager->persist($user);

        $this->addReference(self::NORMAL_USER, $user);

        $manager->flush();
    }
}
