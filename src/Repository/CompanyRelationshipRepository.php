<?php

namespace App\Repository;

use App\Entity\CompanyRelationship as Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyRelationship|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyRelationship|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyRelationship[]    findAll()
 * @method CompanyRelationship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRelationshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }

    public function add(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->flush();
        }
    }

    // /**
    //  * @return CompanyRelationship[] Returns an array of CompanyRelationship objects
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
    public function findOneBySomeField($value): ?CompanyRelationship
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
