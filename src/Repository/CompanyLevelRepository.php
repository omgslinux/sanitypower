<?php

namespace App\Repository;

use App\Entity\CompanyLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyLevel|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyLevel|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyLevel[]    findAll()
 * @method CompanyLevel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyLevel::class);
    }

    // /**
    //  * @return CompanyLevel[] Returns an array of CompanyLevel objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CompanyLevel
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
