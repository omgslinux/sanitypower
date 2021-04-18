<?php

namespace App\Repository;

use App\Entity\StaffTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StaffTitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method StaffTitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method StaffTitle[]    findAll()
 * @method StaffTitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaffTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaffTitle::class);
    }

    // /**
    //  * @return StaffTitle[] Returns an array of StaffTitle objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StaffTitle
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
