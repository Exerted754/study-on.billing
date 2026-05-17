<?php

namespace App\Tests\Command;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PaymentCommandTest extends KernelTestCase
{
    public function testPaymentEndingNotificationCommand(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => 'user@test.local',
        ]);

        $course = $entityManager->getRepository(Course::class)->findOneBy([
            'code' => 'symfony-start',
        ]);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setCourse($course);
        $transaction->setType(Transaction::TYPE_PAYMENT);
        $transaction->setAmount(99.99);
        $transaction->setExpiresAt(new \DateTimeImmutable('tomorrow 12:00:00'));

        $entityManager->persist($transaction);
        $entityManager->flush();

        $application = new Application(self::$kernel);
        $command = $application->find('payment:ending:notification');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString(
            'Отправлено писем: 1',
            $commandTester->getDisplay()
        );
    }

    public function testPaymentReportCommand(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => 'user@test.local',
        ]);

        $course = $entityManager->getRepository(Course::class)->findOneBy([
            'code' => 'php-basic',
        ]);

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setCourse($course);
        $transaction->setType(Transaction::TYPE_PAYMENT);
        $transaction->setAmount(199.99);

        $entityManager->persist($transaction);
        $entityManager->flush();

        $application = new Application(self::$kernel);
        $command = $application->find('payment:report');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString(
            'Отчет отправлен.',
            $commandTester->getDisplay()
        );
    }
}
