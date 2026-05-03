<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function deposit(User $user, float $amount): void
    {
        $this->entityManager->wrapInTransaction(function () use ($user, $amount) {
            $transaction = new Transaction();
            $transaction->setUser($user);
            $transaction->setType(Transaction::TYPE_DEPOSIT);
            $transaction->setAmount($amount);

            $user->setBalance($user->getBalance() + $amount);

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
        });
    }

    public function pay(User $user, Course $course): Transaction
    {
        if ($course->getPrice() === null || $course->getPrice() <= 0) {
            throw new \Exception('Курс бесплатный');
        }

        if ($user->getBalance() < $course->getPrice()) {
            throw new \Exception('На вашем счету недостаточно средств');
        }

        return $this->entityManager->wrapInTransaction(function () use ($user, $course) {
            $transaction = new Transaction();
            $transaction->setUser($user);
            $transaction->setCourse($course);
            $transaction->setType(Transaction::TYPE_PAYMENT);
            $transaction->setAmount($course->getPrice());

            if ($course->getType() === Course::TYPE_RENT) {
                $transaction->setExpiresAt(new \DateTimeImmutable('+1 week'));
            }

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            return $transaction;
        });
    }
}
