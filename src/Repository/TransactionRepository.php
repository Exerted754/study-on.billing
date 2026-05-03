<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByUserWithFilters(User $user, array $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->addSelect('c')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        if (!empty($filters['type'])) {
            $qb
                ->andWhere('t.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['course_code'])) {
            $qb
                ->andWhere('c.code = :courseCode')
                ->setParameter('courseCode', $filters['course_code']);
        }

        if (!empty($filters['skip_expired'])) {
            $qb
                ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
                ->setParameter('now', new \DateTimeImmutable());
        }

        return $qb->getQuery()->getResult();
    }
}
