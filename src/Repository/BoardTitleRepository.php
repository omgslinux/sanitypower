<?php

namespace App\Repository;

use App\Entity\BoardTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BoardTitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method BoardTitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method BoardTitle[]    findAll()
 * @method BoardTitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoardTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardTitle::class);
    }

    // /**
    //  * @return BoardTitle[] Returns an array of BoardTitle objects
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
    public function findOneBySomeField($value): ?BoardTitle
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
