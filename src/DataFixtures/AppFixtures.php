<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@test.local');
        $user->setRoles(['ROLE_USER']);
        $user->setBalance(1000);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'Topparol')
        );
        $manager->persist($user);

        $admin = new User();
        $admin->setEmail('admin@test.local');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setBalance(5000);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'Admin_pass')
        );
        $manager->persist($admin);

        $manager->flush();
    }
}
