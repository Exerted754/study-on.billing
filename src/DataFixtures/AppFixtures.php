<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Course;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private PaymentService $paymentService,
        #[Autowire('%initial_balance%')]
        private float $initialBalance
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

        $course1 = new Course();
        $course1->setCode('php-basic');
        $course1->setType(Course::TYPE_BUY);
        $course1->setPrice(199.99);

        $course2 = new Course();
        $course2->setCode('symfony-start');
        $course2->setType(Course::TYPE_RENT);
        $course2->setPrice(99.99);

        $course3 = new Course();
        $course3->setCode('postgresql-base');
        $course3->setType(Course::TYPE_FREE);
        $course3->setPrice(null);

        $manager->persist($course1);
        $manager->persist($course2);
        $manager->persist($course3);

        $manager->flush();

        $this->paymentService->deposit($user, $this->initialBalance);
        $this->paymentService->deposit($admin, $this->initialBalance);
    }
}
