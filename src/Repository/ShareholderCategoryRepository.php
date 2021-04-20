<?php

namespace App\Repository;

use App\Entity\ShareholderCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ShareholderCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShareholderCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShareholderCategory[]    findAll()
 * @method ShareholderCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShareholderCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShareholderCategory::class);
    }

    // /**
    //  * @return ShareholderCategory[] Returns an array of ShareholderCategory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ShareholderCategory
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
